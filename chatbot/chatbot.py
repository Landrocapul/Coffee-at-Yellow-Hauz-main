#!/usr/bin/env python3
import json
import re
import sys
from datetime import datetime, timezone
from pathlib import Path

if hasattr(sys.stdout, "reconfigure"):
    sys.stdout.reconfigure(encoding="utf-8")


BASE_DIR = Path(__file__).resolve().parent
INTENTS_FILE = BASE_DIR / "intents.json"
LEARNED_FILE = BASE_DIR / "learned_intents.json"


def normalize(text):
    return re.sub(r"[^a-z0-9\s]", " ", text.lower()).split()


def load_intents():
    with INTENTS_FILE.open("r", encoding="utf-8") as handle:
        built_in = json.load(handle).get("intents", [])

    if not LEARNED_FILE.exists():
        return built_in

    with LEARNED_FILE.open("r", encoding="utf-8") as handle:
        learned = json.load(handle).get("intents", [])

    return learned + built_in


def save_learned_intents(intents):
    payload = {"intents": intents}
    temp_file = LEARNED_FILE.with_suffix(".tmp")
    with temp_file.open("w", encoding="utf-8") as handle:
        json.dump(payload, handle, ensure_ascii=False, indent=2)
        handle.write("\n")
    temp_file.replace(LEARNED_FILE)


def score_intent(tokens, intent):
    keywords = set(intent.get("keywords", []))
    patterns = intent.get("patterns", [])
    learned_weight = 2 if intent.get("source") == "learned" else 1
    score = sum(3 * learned_weight for token in tokens if token in keywords)

    query = " ".join(tokens)
    for pattern in patterns:
        pattern_tokens = normalize(pattern)
        if query == " ".join(pattern_tokens):
            score += 15 * learned_weight
        elif pattern_tokens and all(token in tokens for token in pattern_tokens):
            score += 5 * learned_weight
        elif pattern.lower() in query:
            score += 4 * learned_weight

    return score


def make_keywords(question):
    stop_words = {
        "a", "an", "and", "are", "can", "do", "does", "for", "how", "i", "in",
        "is", "it", "of", "on", "or", "the", "to", "what", "when", "where",
        "with", "you"
    }
    return sorted({token for token in normalize(question) if len(token) > 2 and token not in stop_words})


def answer(message):
    tokens = normalize(message)
    intents = load_intents()

    if not tokens:
        return {
            "reply": "Ask me about taking orders, payments, tables, tickets, stock, reports, or account settings.",
            "matched_intent": "empty",
            "confidence": 0,
        }

    ranked = sorted(
        ((score_intent(tokens, intent), intent) for intent in intents),
        key=lambda item: item[0],
        reverse=True,
    )

    best_score, best_intent = ranked[0] if ranked else (0, None)
    if best_score <= 0 or not best_intent:
        return {
            "reply": (
                "I am not sure yet. Try asking things like: how do I take an order, "
                "how do I mark a ticket complete, how do I restock an item, or how do I view sales?"
            ),
            "matched_intent": "fallback",
            "confidence": 0,
            "needs_training": True,
        }

    return {
        "reply": best_intent["response"],
        "matched_intent": best_intent.get("tag", "unknown"),
        "confidence": min(100, best_score * 10),
        "needs_training": best_score < 6,
        "source": best_intent.get("source", "built_in"),
    }


def learn(question, response, learned_by="staff"):
    question = question.strip()
    response = response.strip()

    if len(question) < 3:
        raise ValueError("Question is too short.")
    if len(response) < 8:
        raise ValueError("Answer is too short.")

    learned = []
    if LEARNED_FILE.exists():
        with LEARNED_FILE.open("r", encoding="utf-8") as handle:
            learned = json.load(handle).get("intents", [])

    normalized_question = " ".join(normalize(question))
    replacement = {
        "tag": "learned_" + re.sub(r"[^a-z0-9_]", "_", normalized_question[:40]).strip("_"),
        "patterns": [question],
        "keywords": make_keywords(question),
        "response": response,
        "source": "learned",
        "learned_by": learned_by,
        "updated_at": datetime.now(timezone.utc).isoformat(),
    }

    for index, intent in enumerate(learned):
        existing_patterns = [" ".join(normalize(pattern)) for pattern in intent.get("patterns", [])]
        if normalized_question in existing_patterns:
            learned[index] = replacement
            save_learned_intents(learned)
            return {"learned": True, "updated": True, "intent": replacement}

    learned.insert(0, replacement)
    save_learned_intents(learned)
    return {"learned": True, "updated": False, "intent": replacement}


def main():
    if len(sys.argv) > 1 and sys.argv[1] == "--learn":
        question = sys.argv[2] if len(sys.argv) > 2 else ""
        response = sys.argv[3] if len(sys.argv) > 3 else ""
        learned_by = sys.argv[4] if len(sys.argv) > 4 else "staff"
        try:
            result = learn(question, response, learned_by)
        except ValueError as error:
            result = {"learned": False, "error": str(error)}
    else:
        message = " ".join(sys.argv[1:]).strip()
        result = answer(message)

    print(json.dumps(result, ensure_ascii=False))


if __name__ == "__main__":
    main()
