# Staff Guide Chatbot

This Python chatbot is called by `api.php?action=staff_chatbot`.

Install Python 3 on the web server, then restart Apache so PHP can find it. If Python is not on `PATH`, set an environment variable named `CHATBOT_PYTHON` to the full interpreter path, for example `C:\Python312\python.exe`.

## Run Locally

```bash
python chatbot.py "how do I take an order"
```

It returns JSON with a staff guidance reply, matched intent, and confidence score.

## Learning

The staff widget can teach the bot new answers when it is unsure. Lessons are saved in `learned_intents.json` and loaded before the built-in intents, so a taught answer can be used immediately.

You can also teach it from the command line:

```bash
python chatbot.py --learn "How do I void an order?" "Open Tickets, choose the order, then cancel it before final settlement." "admin"
```
