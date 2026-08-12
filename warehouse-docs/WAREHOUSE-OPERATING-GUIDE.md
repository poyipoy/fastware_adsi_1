# Warehouse Consumable — Operating Guide

## Before first use

1. An authorized Warehouse user creates an active consumable. Item Code is also used as the item barcode; current stock starts at zero.
2. Make sure the verifier is a login-enabled user with an active assignment in `Logistic & Warehouse`, `Production`, or `PDCA, Inventory, Procurement & IT`, or is an Administrator. No employee-ID mapping is needed.
3. Record opening balance with **Stock Adjustment**. Never edit `current_stock` directly.

## Stock In / Stock Out

1. Open **Warehouse → Form Stock In/Out**.
2. Select the allowed movement type, scan the item barcode, and confirm the item summary.
3. Enter a positive quantity. For **Stock In**, enter the storage location; this becomes the item's active location after the movement commits. For **Stock Out**, the current item location is shown read-only and no location input is required.
4. Scan the employee's numeric NPK barcode. The system resolves it directly from `users.npk`, verifies Warehouse access, and shows name, NPK, and section.
5. Review the projected stock and explicitly save. The receipt shows the immutable transaction number and before/after stock.

Stock Out is rejected when stock would become negative. A repeated submission with the same idempotency key returns the original result rather than creating a second movement.

## Low stock and history

The dashboard marks `LOW` when current stock is at or below minimum and `OUT` at zero. Use the Stock In action only when authorized. History filters use the transaction snapshots, including the section at the time of movement.

## Reversal and adjustment

- Reversal is for correcting a recorded transaction. It requires a reason and an authorized reversal actor; the original row remains unchanged.
- Adjustment requires an authorized Warehouse actor plus direction, quantity, reason category, detailed reason, and NPK verification.
- If reversal or adjustment would make stock negative, the entire operation is rejected.

## Export

Every user with Warehouse access can export filtered transaction snapshots to XLSX. The configured row limit is enforced synchronously.

## Scanner troubleshooting

- Use a plain text scanner input and keep the scanner's Enter/Tab terminator enabled.
- Item scans trim surrounding whitespace and trailing CR/LF/TAB while preserving the Item Code. Employee scans must be numeric; leading zeroes are normalized to the integer value stored in `users.npk`.
- If an NPK is unknown, inactive, ambiguous, or has no Warehouse access, retry once and then ask an Administrator to verify the user and active job assignment. Do not paste scanned codes into notes or screenshots.

## Safety rules

Do not edit Warehouse tables directly, delete transaction rows, or run destructive database commands against a non-`_testing` database. Camera scanning, offline mode, multi-item batch, approvals, replenishment, and ERP integration are not part of this module.
