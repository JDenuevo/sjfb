<!-- ══ SHIPPING AND REFUND POLICY MODAL ══════════════════════════════════════════════════ -->
<div id="shippingRefundModal" class="modal-overlay hidden fixed inset-0 z-[80] flex items-center justify-center bg-black/50 p-3">
  <div class="modal-box modal-box-md flex flex-col w-full sm:max-w-2xl max-h-[90vh] bg-white rounded-xl shadow-lg border border-gray-200">

    <!-- Header -->
    <div class="modal-header flex justify-between items-center py-3 px-4 border-b border-gray-200 ">
      <div class="flex items-center gap-x-3">
        <div class="flex items-center justify-center size-9 rounded-full bg-emerald-50 ">
          <svg class="size-5 text-emerald-600 " fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm-8 2a2 2 0 1 1-4 0 2 2 0 0 1 4 0z"/>
          </svg>
        </div>
        <div>
          <h3 class="font-bold text-gray-800 ">Shipping and Refund Policy</h3>
          <p class="text-xs text-gray-500 ">Last Updated: June 24, 2026</p>
        </div>
      </div>
      <button type="button" class="flex justify-center items-center size-8 text-gray-500 hover:bg-gray-100 rounded-full  " onclick="closeShippingRefundModal('shippingRefundModal')">
        <span class="sr-only">Close</span>
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6L6 18M6 6l12 12"/></svg>
      </button>
    </div>

    <!-- Body (scrollable) -->
    <div class="modal-body overflow-y-auto px-4 py-4 text-sm text-gray-700  space-y-5">

      <p>
        At St. Joseph Fish Brokerage Inc., we are committed to providing fresh, high-quality seafood products and
        excellent customer service. Please read this Shipping and Refund Policy carefully before placing an order.
      </p>

      <!-- Shipping Policy -->
      <div>
        <h3 class="text-base font-bold text-gray-900  mb-3 pb-1 border-b border-gray-200 ">Shipping Policy</h3>

        <section class="mb-4">
          <h4 class="font-semibold text-gray-900  mb-1">Order Processing</h4>

          <p class="font-medium text-gray-800  mt-2">Fresh Seafood Products</p>
          <p>
            To ensure maximum freshness and quality, orders for fresh seafood products are processed on the next
            business day after order confirmation and payment verification. As our seafood sourcing, handling, and
            preparation operations are conducted during nighttime hours, same-day processing may not always be
            available.
          </p>

          <p class="font-medium text-gray-800  mt-2">Dried Fish and Tinapa Products</p>
          <p>
            Orders containing dried fish, tinapa, and other non-perishable seafood products may be processed and
            dispatched immediately, subject to product availability and operational schedules.
          </p>
        </section>

        <section class="mb-4">
          <h4 class="font-semibold text-gray-900  mb-1">Delivery Fees</h4>
          <p class="mb-1">
            Delivery fees are shouldered by the customer and are not included in the listed product prices unless
            otherwise stated. Shipping charges may vary depending on:
          </p>
          <ul class="list-disc list-inside ml-2 space-y-0.5">
            <li>Delivery location</li>
            <li>Order quantity, weight, and volume</li>
            <li>Courier or delivery service rates</li>
            <li>Special handling requirements</li>
          </ul>
          <p class="mt-2">Applicable delivery charges will be added to the total order amount before order confirmation.</p>
        </section>

        <section class="mb-4">
          <h4 class="font-semibold text-gray-900  mb-1">Delivery Schedule</h4>
          <p class="mb-1">Estimated delivery times may vary depending on:</p>
          <ul class="list-disc list-inside ml-2 space-y-0.5">
            <li>Delivery destination</li>
            <li>Traffic conditions</li>
            <li>Weather conditions</li>
            <li>Courier availability</li>
            <li>Public holidays and peak seasons</li>
            <li>Other circumstances beyond our reasonable control</li>
          </ul>
          <p class="mt-2">
            While we strive to deliver orders on schedule, St. Joseph Fish Brokerage Inc. does not guarantee exact
            delivery times and shall not be liable for delays caused by events beyond our control.
          </p>
        </section>

        <section class="mb-4">
          <h4 class="font-semibold text-gray-900  mb-1">Cash on Pick-up (COP)</h4>
          <p>
            Customers may place orders online and settle payment upon collection at the designated pick-up
            location. Orders placed under Cash on Pick-up (COP) must be claimed within the agreed pick-up
            schedule. Unclaimed orders may be cancelled and released for sale without prior notice.
          </p>
        </section>

        <section class="mb-4">
          <h4 class="font-semibold text-gray-900  mb-1">Receiving Your Order</h4>
          <p class="mb-1">
            Customers or their authorized representatives must be available to receive and inspect the products
            upon delivery. Upon receipt, customers are encouraged to immediately verify:
          </p>
          <ul class="list-disc list-inside ml-2 space-y-0.5">
            <li>Product quantity</li>
            <li>Product condition</li>
            <li>Accuracy of the order</li>
          </ul>
          <p class="mt-2">
            The Company shall not be responsible for spoilage, damage, or quality deterioration resulting from
            improper handling, delayed receipt, or improper storage after delivery.
          </p>
        </section>

        <section>
          <h4 class="font-semibold text-gray-900  mb-1">Cancellation Policy</h4>
          <p class="mb-1">
            Due to the perishable nature of seafood products, customers may request cancellation of their order
            within three (3) hours from the time the order is placed. After the three (3)-hour cancellation period
            has elapsed, the order shall be considered final and non-cancellable.
          </p>
          <p>
            Once an order is received, products may already have been reserved, sourced, processed, packed, or
            prepared specifically for the customer. Because fresh seafood products cannot be returned to
            fishermen, suppliers, producers, or resold as new inventory, cancellation requests beyond the
            three (3)-hour period will not be eligible for a refund.
          </p>
        </section>
      </div>

      <!-- Refund Policy -->
      <div>
        <h3 class="text-base font-bold text-gray-900  mb-3 pb-1 border-b border-gray-200 ">Refund Policy</h3>

        <p class="mb-4">
          Due to the perishable nature of seafood products, all sales are generally considered final. Refunds,
          replacements, or store credits will only be considered for verified quality-related issues.
        </p>

        <section class="mb-4">
          <h4 class="font-semibold text-gray-900  mb-1">Eligible Refund Requests</h4>
          <p class="mb-1">A refund, replacement, or store credit may be approved only if:</p>
          <ul class="list-disc list-inside ml-2 space-y-0.5">
            <li>The product is spoiled upon arrival;</li>
            <li>The product is damaged before delivery;</li>
            <li>The product is not fit for human consumption upon receipt; or</li>
            <li>The customer receives an incorrect product due to an error on our part.</li>
          </ul>
          <p class="mt-2">All claims are subject to review and approval by St. Joseph Fish Brokerage Inc.</p>
        </section>

        <section class="mb-4">
          <h4 class="font-semibold text-gray-900  mb-1">Required Proof for Refund Requests</h4>
          <p class="mb-1">
            To qualify for a refund, replacement, or store credit, customers must submit the following within
            twenty-four (24) hours of receiving the order:
          </p>
          <p class="font-medium text-gray-800  mt-2">Video Evidence</p>
          <p class="mb-1">A clear and continuous unedited video showing:</p>
          <ul class="list-disc list-inside ml-2 space-y-0.5">
            <li>The sealed package upon arrival;</li>
            <li>The complete unpacking process; and</li>
            <li>The condition of the products immediately after opening.</li>
          </ul>

          <p class="font-medium text-gray-800  mt-2">Supporting Information</p>
          <p class="mb-1">Customers may also be required to provide:</p>
          <ul class="list-disc list-inside ml-2 space-y-0.5">
            <li>Clear photographs of the affected products;</li>
            <li>Order number;</li>
            <li>Customer name; and</li>
            <li>Description of the issue.</li>
          </ul>
          <p class="mt-2">Claims submitted without sufficient evidence may be denied.</p>
        </section>

        <section class="mb-4">
          <h4 class="font-semibold text-gray-900  mb-1">Non-Refundable Situations</h4>
          <p class="mb-1">Refunds, replacements, or credits will not be granted for:</p>
          <ul class="list-disc list-inside ml-2 space-y-0.5">
            <li>Cancellation requests made more than three (3) hours after placing the order;</li>
            <li>Change of mind;</li>
            <li>Incorrect orders placed by the customer;</li>
            <li>Claims submitted without the required proof;</li>
            <li>Improper storage or handling after delivery;</li>
            <li>Product deterioration caused by customer negligence;</li>
            <li>Customer unavailability during delivery;</li>
            <li>Delays caused by weather conditions, courier issues, or force majeure events;</li>
            <li>Natural variations in seafood size, weight, color, texture, or appearance.</li>
          </ul>
        </section>

        <section class="mb-4">
          <h4 class="font-semibold text-gray-900  mb-1">Claim Submission Period</h4>
          <p>
            All refund requests must be submitted within twenty-four (24) hours from the recorded delivery time.
            Claims submitted beyond this period may no longer be eligible for review.
          </p>
        </section>

        <section>
          <h4 class="font-semibold text-gray-900  mb-1">Resolution of Approved Claims</h4>
          <p class="mb-1">
            Upon approval of a claim, St. Joseph Fish Brokerage Inc. may provide one of the following remedies:
          </p>
          <ul class="list-disc list-inside ml-2 space-y-0.5">
            <li>Product replacement;</li>
            <li>Store credit;</li>
            <li>Partial refund; or</li>
            <li>Full refund.</li>
          </ul>
          <p class="mt-2">
            The appropriate resolution will be determined based on the nature of the issue and the outcome of
            the investigation.
          </p>
        </section>
      </div>

      <section>
        <h4 class="font-semibold text-gray-900  mb-1">Contact Us</h4>
        <p class="mb-2">
          For shipping concerns, cancellation requests, refund inquiries, or order-related concerns, please contact:
        </p>
        <div class="rounded-lg bg-gray-50  p-3 space-y-1">
          <p class="font-medium text-gray-800 ">St. Joseph Fish Brokerage Inc.</p>
          <p>Email: <a href="mailto:marketing@fishbrokers.net" class="text-blue-600 hover:underline ">marketing@fishbrokers.net</a></p>
          <p>Phone: (+63) 946-497-3689</p>
          <p>Website: <a href="https://www.fishbrokers.net" target="_blank" class="text-blue-600 hover:underline ">www.fishbrokers.net</a></p>
        </div>
      </section>

    </div>

    <!-- Footer -->
    <div class="modal-footer flex justify-end items-center gap-x-2 py-3 px-4 border-t border-gray-200 ">
      <button type="button" onclick="closeShippingRefundModal('shippingRefundModal')"
        class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-sm hover:bg-gray-50    ">
        Close
      </button>
    </div>

  </div>
</div>

<script>
// ── Shipping and Refund Policy modal ─────────────────────────────────────────────────
// openModal(id) / closeShippingRefundModal(id) are already declared globally in
// privacy_policy_modal.php — do NOT redeclare them here (see note in
// terms_condition_modal.php for why that causes a collision bug).
// This file only adds its own backdrop-click listener, scoped to #shippingRefundModal.

document.getElementById('shippingRefundModal')?.addEventListener('click', function (e) {
  if (e.target === this) closeShippingRefundModal('shippingRefundModal');
});
</script>