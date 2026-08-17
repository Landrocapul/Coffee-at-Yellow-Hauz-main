<?php
// Customer order status is intentionally not exposed by order number alone.
// Add a token-backed status endpoint later if customers need remote tracking.
http_response_code(403);
?><!doctype html><title>Customer order status</title><p>For an order update, please ask a Yellow Hauz staff member.</p>
