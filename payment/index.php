<?php
require_once 'connection.php';
include 'includes/header.php';
?>

<div class="max-w-md mx-auto bg-white rounded-lg shadow-md p-6">
    <h2 class="text-2xl font-bold mb-6">Create Payment Request</h2>
    
    <form action="create_payment.php" method="POST">
        <div class="mb-4">
            <label class="block text-gray-700 mb-2" for="amount">Amount (INR)</label>
            <input type="number" step="0.01" name="amount" id="amount" required
                   class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500">
        </div>
        
        <div class="mb-4">
            <label class="block text-gray-700 mb-2" for="customer_name">Customer Name</label>
            <input type="text" name="customer_name" id="customer_name" required
                   class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500">
        </div>
        
        <div class="mb-4">
            <label class="block text-gray-700 mb-2" for="description">Description</label>
            <textarea name="description" id="description" required
                      class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500"></textarea>
        </div>
        
        <button type="submit" class="w-full bg-blue-500 text-white py-2 px-4 rounded-lg hover:bg-blue-600">
            Generate Payment Link
        </button>
    </form>
</div>

<?php include 'includes/footer.php'; ?>
