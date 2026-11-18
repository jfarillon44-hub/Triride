<?php
$page_title = 'Payment Invoice';
$page_header_title = 'Payment';
$active_tab = 'home';
$show_back_btn = false;

require_once 'header.php';

// Get completed but unpaid ride info
$ride_sql = "SELECT r.*, d.fullname AS driver_name FROM rides r 
             LEFT JOIN drivers d ON r.driver_id = d.id 
             JOIN payments p ON p.ride_id = r.id
             WHERE r.user_id = ? AND r.status = 'Completed' AND p.status = 'Pending'
             ORDER BY r.id DESC LIMIT 1";
$stmt = $conn->prepare($ride_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$ride_res = $stmt->get_result();

if ($ride_res->num_rows === 0) {
    // No completed unpaid rides, go to dashboard
    header("Location: dashboard.php");
    exit();
}

$ride = $ride_res->fetch_assoc();
$distance = floatval($ride['distance']);
$fare = floatval($ride['fare']);

// Load rates
$settings_data = get_settings();
$base_fare = floatval($settings_data['base_fare']);
$per_km_rate = floatval($settings_data['per_km_rate']);

// Calculations for itemized summary
$base_amount = $base_fare;
$distance_fee = $fare - $base_fare;
if ($distance_fee < 0) $distance_fee = 0.00;
?>

<div class="app-content">
    <div style="background: white; border-radius: var(--radius-md); border: 1px solid var(--border-color); padding: 1.25rem; box-shadow: var(--shadow-sm); margin-bottom: 1rem;">
        
        <h4 style="font-size: 1rem; color: var(--primary-color); margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">
            <i class="fa-solid fa-file-invoice-dollar"></i> Trip Summary
        </h4>

        <!-- Itemized invoice details -->
        <div style="display: flex; flex-direction: column; gap: 0.75rem; font-size: 0.85rem; margin-bottom: 1.25rem;">
            <div style="display: flex; justify-content: space-between;">
                <span class="text-muted">Distance</span>
                <strong><?php echo $distance; ?> km</strong>
            </div>
            <div style="display: flex; justify-content: space-between;">
                <span class="text-muted">Base Fare</span>
                <span>P<?php echo number_format($base_amount, 2); ?></span>
            </div>
            <div style="display: flex; justify-content: space-between;">
                <span class="text-muted">Distance Fee</span>
                <span>P<?php echo number_format($distance_fee, 2); ?></span>
            </div>
            <div style="display: flex; justify-content: space-between; border-top: 1px solid var(--border-color); padding-top: 0.75rem; font-size: 1.05rem;">
                <strong style="color: var(--primary-color);">Total Fare</strong>
                <strong style="color: var(--primary-color);">P<?php echo number_format($fare, 2); ?></strong>
            </div>
        </div>

        <!-- Payment Method form -->
        <h4 style="font-size: 1rem; color: var(--primary-color); margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem; border-top: 1px solid var(--border-color); padding-top: 1rem;">
            <i class="fa-solid fa-wallet"></i> Payment Method
        </h4>

        <form id="payment-form" onsubmit="submitPayment(event)">
            <input type="hidden" id="pay_ride_id" value="<?php echo $ride['id']; ?>">

            <!-- Wallet balance indicator -->
            <div style="background: var(--primary-light); color: var(--primary-dark); border-radius: var(--radius-md); padding: 0.75rem 1rem; font-size: 0.85rem; margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: center;">
                <span><i class="fa-solid fa-wallet"></i> Your Wallet Balance:</span>
                <strong>P<?php echo number_format($passenger['wallet_balance'], 2); ?></strong>
            </div>
            <p style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 1rem;">Note: Selecting digital payment methods (GCash, Maya, PayPal) will deduct the fare directly from your wallet balance.</p>

            <div style="display: flex; flex-direction: column; gap: 0.75rem; margin-bottom: 1.5rem;">
                <label style="display: flex; align-items: center; gap: 0.75rem; background: var(--bg-light); border: 1.5px solid var(--border-color); border-radius: var(--radius-md); padding: 0.75rem; cursor: pointer; font-size: 0.9rem;">
                    <input type="radio" name="payment_method" value="Cash" checked>
                    <i class="fa-solid fa-money-bill-wave" style="color: #28a745; font-size: 1.15rem;"></i>
                    <span>Cash Payment</span>
                </label>

                <label style="display: flex; align-items: center; gap: 0.75rem; background: var(--bg-light); border: 1.5px solid var(--border-color); border-radius: var(--radius-md); padding: 0.75rem; cursor: pointer; font-size: 0.9rem;">
                    <input type="radio" name="payment_method" value="GCash">
                    <span style="font-weight: 700; color: #1e3a8a; font-size: 1rem; width: 22px;">G</span>
                    <span>GCash (Wallet Deduction)</span>
                </label>

                <label style="display: flex; align-items: center; gap: 0.75rem; background: var(--bg-light); border: 1.5px solid var(--border-color); border-radius: var(--radius-md); padding: 0.75rem; cursor: pointer; font-size: 0.9rem;">
                    <input type="radio" name="payment_method" value="Maya">
                    <span style="font-weight: 700; color: #10b981; font-size: 1rem; width: 22px;">M</span>
                    <span>Maya (Wallet Deduction)</span>
                </label>

                <label style="display: flex; align-items: center; gap: 0.75rem; background: var(--bg-light); border: 1.5px solid var(--border-color); border-radius: var(--radius-md); padding: 0.75rem; cursor: pointer; font-size: 0.9rem;">
                    <input type="radio" name="payment_method" value="PayPal">
                    <i class="fa-brands fa-paypal" style="color: #2563eb; font-size: 1.15rem;"></i>
                    <span>PayPal (Wallet Deduction)</span>
                </label>
            </div>

            <button type="submit" class="btn btn-primary" id="btn-pay-submit">
                Confirm Payment
            </button>
        </form>

    </div>
</div>

<script>
    function submitPayment(e) {
        e.preventDefault();
        const btn = document.getElementById('btn-pay-submit');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Processing...';

        const method = document.querySelector('input[name="payment_method"]:checked').value;
        const rideId = document.getElementById('pay_ride_id').value;

        fetch('../api/wallet_action.php?action=pay_ride', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ ride_id: rideId, method: method })
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                alert('Payment Successful!');
                window.location.href = 'dashboard.php';
            } else {
                alert(data.message);
                btn.disabled = false;
                btn.innerHTML = 'Confirm Payment';
            }
        })
        .catch(err => {
            console.error(err);
            btn.disabled = false;
            btn.innerHTML = 'Confirm Payment';
        });
    }
</script>

<?php require_once 'footer.php'; ?>
