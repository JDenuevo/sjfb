<!-- ══ TERMS AND CONDITIONS MODAL ══════════════════════════════════════════════════ -->
<div id="termsModal" class="modal-overlay hidden fixed inset-0 z-[80] flex items-center justify-center bg-black/50 p-3">
  <div class="modal-box modal-box-md flex flex-col w-full sm:max-w-2xl max-h-[90vh] bg-white rounded-xl shadow-lg border border-gray-200  ">

    <!-- Header -->
    <div class="modal-header flex justify-between items-center py-3 px-4 border-b border-gray-200 ">
      <div class="flex items-center gap-x-3">
        <div class="flex items-center justify-center size-9 rounded-full bg-orange-50 ">
          <svg class="size-5 text-orange-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z"/>
          </svg>
        </div>
        <div>
          <h3 class="font-bold text-gray-800 ">Terms & Conditions</h3>
          <p class="text-xs text-gray-500 ">Effective Date: June 24, 2026</p>
        </div>
      </div>
      <button type="button" class="flex justify-center items-center size-8 text-gray-500 hover:bg-gray-100 rounded-full  " onclick="closeModal('termsModal')">
        <span class="sr-only">Close</span>
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6L6 18M6 6l12 12"/></svg>
      </button>
    </div>

    <!-- Body (scrollable) -->
    <div class="modal-body overflow-y-auto px-4 py-4 text-sm text-gray-700  space-y-5">

      <p>
        Welcome to FishBrokers.net, operated by St. Joseph Fish Brokerage Inc. ("Company," "we," "our," or "us").
        By accessing, browsing, registering an account, purchasing products, or using any services provided through
        this website, you agree to be bound by these Terms and Conditions. If you do not agree to these Terms,
        please do not use this website.
      </p>

      <section>
        <h4 class="font-semibold text-gray-900  mb-1">1. Acceptance of Terms</h4>
        <p class="mb-1">By using this website, you represent that:</p>
        <ul class="list-disc list-inside ml-2 space-y-0.5">
          <li>You are at least 18 years old or have legal authority to enter into binding agreements.</li>
          <li>The information you provide is accurate and complete.</li>
          <li>You will comply with all applicable laws and regulations.</li>
        </ul>
      </section>

      <section>
        <h4 class="font-semibold text-gray-900  mb-1">2. Company Information</h4>
        <p class="mb-2">This website is owned and operated by:</p>
        <div class="rounded-lg bg-gray-50  p-3 space-y-1">
          <p class="font-medium text-gray-800 ">St. Joseph Fish Brokerage Inc.</p>
          <p>Address: Bulungan Avenue corner HACCP Street, NFPC NBBS, Navotas City, Philippines</p>
          <p>Email: <a href="mailto:marketing@fishbrokers.net" class="text-blue-600 hover:underline ">marketing@fishbrokers.net</a></p>
          <p>Phone: (+63) 946-497-3689</p>
        </div>
      </section>

      <section>
        <h4 class="font-semibold text-gray-900  mb-1">3. Products and Services</h4>
        <p class="mb-1">
          The Company provides seafood products, fish brokerage services, distribution services, logistics support,
          and related services. All products displayed on the website are subject to:
        </p>
        <ul class="list-disc list-inside ml-2 space-y-0.5">
          <li>Availability</li>
          <li>Market supply conditions</li>
          <li>Seasonal availability</li>
          <li>Regulatory requirements</li>
          <li>Price changes without prior notice</li>
        </ul>
        <p class="mt-2">We reserve the right to discontinue, modify, or limit products and services at any time.</p>
      </section>

      <section>
        <h4 class="font-semibold text-gray-900  mb-1">4. Account Registration</h4>
        <p class="mb-1">To access certain features, you may be required to create an account. You agree to:</p>
        <ul class="list-disc list-inside ml-2 space-y-0.5">
          <li>Provide accurate registration information.</li>
          <li>Maintain the confidentiality of your account credentials.</li>
          <li>Notify us immediately of unauthorized account access.</li>
          <li>Accept responsibility for activities occurring under your account.</li>
        </ul>
        <p class="mt-2">We reserve the right to suspend or terminate accounts containing false, misleading, or fraudulent information.</p>
      </section>

      <section>
        <h4 class="font-semibold text-gray-900  mb-1">5. Orders and Purchases</h4>
        <p class="mb-1">All orders placed through the website are subject to acceptance and verification. We reserve the right to:</p>
        <ul class="list-disc list-inside ml-2 space-y-0.5">
          <li>Refuse any order</li>
          <li>Limit quantities purchased</li>
          <li>Cancel suspicious transactions</li>
          <li>Verify customer information before processing</li>
        </ul>
        <p class="mt-2">
          An order confirmation does not guarantee acceptance of an order. A sales contract is considered completed
          only after payment verification and order confirmation by the Company.
        </p>
      </section>

      <section>
        <h4 class="font-semibold text-gray-900  mb-1">6. Pricing</h4>
        <p class="mb-1">Prices displayed on the website:</p>
        <ul class="list-disc list-inside ml-2 space-y-0.5">
          <li>May change without prior notice</li>
          <li>May vary due to market conditions</li>
          <li>May exclude shipping, handling, taxes, or other applicable charges unless otherwise stated</li>
        </ul>
        <p class="mt-2">In the event of pricing errors, we reserve the right to cancel or revise affected orders.</p>
      </section>

      <section>
        <h4 class="font-semibold text-gray-900  mb-1">7. Payment Terms</h4>
        <p class="mb-1">Accepted payment methods may include:</p>
        <ul class="list-disc list-inside ml-2 space-y-0.5">
          <li>Credit Cards</li>
          <li>Debit Cards</li>
          <li>GCash</li>
          <li>Maya</li>
          <li>QRPH</li>
          <li>GrabPay</li>
          <li>Bank Transfers</li>
          <li>Cash on Pickup</li>
        </ul>
        <p class="mt-2">Customers agree to provide valid payment information. Fraudulent transactions may result in order cancellation and legal action.</p>
      </section>

      <section>
        <h4 class="font-semibold text-gray-900  mb-1">8. Shipping and Delivery</h4>
        <p class="mb-1">Delivery timelines are estimates only and may be affected by:</p>
        <ul class="list-disc list-inside ml-2 space-y-0.5">
          <li>Weather conditions</li>
          <li>Transportation delays</li>
          <li>Supply chain disruptions</li>
          <li>Force majeure events</li>
          <li>Regulatory inspections</li>
        </ul>
        <p class="mt-2">
          The Company shall not be liable for delays beyond its reasonable control. Risk of loss and ownership
          transfer upon successful delivery and acceptance by the customer.
        </p>
      </section>

      <section>
        <h4 class="font-semibold text-gray-900  mb-1">9. Product Quality and Inspection</h4>
        <p class="mb-1">Customers are encouraged to inspect products immediately upon receipt. Any concerns regarding:</p>
        <ul class="list-disc list-inside ml-2 space-y-0.5">
          <li>Damaged products</li>
          <li>Incorrect deliveries</li>
          <li>Quality issues</li>
          <li>Missing items</li>
        </ul>
        <p class="mt-2">
          must be reported within twenty-four (24) hours of delivery. Failure to report within this period may be
          deemed acceptance of the delivered products.
        </p>
      </section>

      <section>
        <h4 class="font-semibold text-gray-900  mb-1">10. Returns, Refunds, and Replacements</h4>
        <p class="mb-1">
          Due to the perishable nature of seafood products, returns may only be accepted under the following circumstances:
        </p>
        <ul class="list-disc list-inside ml-2 space-y-0.5">
          <li>Wrong item delivered</li>
          <li>Product delivered in damaged condition</li>
          <li>Product quality substantially differs from the confirmed order</li>
          <li>Delivery error attributable to the Company</li>
        </ul>
        <p class="mt-2 mb-1">Approved claims may be resolved through:</p>
        <ul class="list-disc list-inside ml-2 space-y-0.5">
          <li>Replacement</li>
          <li>Store credit</li>
          <li>Partial refund</li>
          <li>Full refund</li>
        </ul>
        <p class="mt-2">at the sole discretion of the Company after investigation.</p>
      </section>

      <section>
        <h4 class="font-semibold text-gray-900  mb-1">11. Intellectual Property</h4>
        <p class="mb-1">All content on this website, including but not limited to:</p>
        <ul class="list-disc list-inside ml-2 space-y-0.5">
          <li>Logos</li>
          <li>Trademarks</li>
          <li>Product descriptions</li>
          <li>Text</li>
          <li>Graphics</li>
          <li>Images</li>
          <li>Videos</li>
          <li>Website design</li>
          <li>Software</li>
        </ul>
        <p class="mt-2">
          is owned by or licensed to the Company and protected by intellectual property laws. No content may be
          copied, reproduced, distributed, or modified without prior written permission.
        </p>
      </section>

      <section>
        <h4 class="font-semibold text-gray-900  mb-1">12. Prohibited Activities</h4>
        <p class="mb-1">Users shall not:</p>
        <ul class="list-disc list-inside ml-2 space-y-0.5">
          <li>Use the website for unlawful purposes</li>
          <li>Attempt unauthorized access to systems</li>
          <li>Upload malicious software or code</li>
          <li>Interfere with website operations</li>
          <li>Misrepresent their identity</li>
          <li>Violate intellectual property rights</li>
          <li>Engage in fraudulent transactions</li>
        </ul>
        <p class="mt-2">Violations may result in account suspension, legal action, or both.</p>
      </section>

      <section>
        <h4 class="font-semibold text-gray-900  mb-1">13. User Submissions</h4>
        <p class="mb-1">
          Any information, feedback, reviews, comments, resumes, or materials submitted through the website may be
          used by the Company for legitimate business purposes, subject to applicable privacy laws. Users warrant
          that submitted materials:
        </p>
        <ul class="list-disc list-inside ml-2 space-y-0.5">
          <li>Are accurate</li>
          <li>Do not violate third-party rights</li>
          <li>Do not contain unlawful content</li>
        </ul>
      </section>

      <section>
        <h4 class="font-semibold text-gray-900  mb-1">14. Limitation of Liability</h4>
        <p class="mb-1">To the fullest extent permitted by law, the Company shall not be liable for:</p>
        <ul class="list-disc list-inside ml-2 space-y-0.5">
          <li>Indirect damages</li>
          <li>Incidental damages</li>
          <li>Consequential damages</li>
          <li>Loss of profits</li>
          <li>Loss of business opportunities</li>
          <li>Data loss</li>
          <li>Service interruptions</li>
        </ul>
        <p class="mt-2">arising from the use of or inability to use the website or services.</p>
      </section>

      <section>
        <h4 class="font-semibold text-gray-900  mb-1">15. Disclaimer</h4>
        <p class="mb-1">The website and its content are provided on an "as-is" and "as-available" basis. The Company makes no warranties regarding:</p>
        <ul class="list-disc list-inside ml-2 space-y-0.5">
          <li>Continuous availability</li>
          <li>Error-free operation</li>
          <li>Accuracy of website content</li>
          <li>Freedom from viruses or harmful components</li>
        </ul>
      </section>

      <section>
        <h4 class="font-semibold text-gray-900  mb-1">16. Force Majeure</h4>
        <p class="mb-1">
          The Company shall not be liable for delays or failures caused by circumstances beyond reasonable control, including:
        </p>
        <ul class="list-disc list-inside ml-2 space-y-0.5">
          <li>Natural disasters</li>
          <li>Floods</li>
          <li>Typhoons</li>
          <li>Earthquakes</li>
          <li>Fires</li>
          <li>Government actions</li>
          <li>Labor disputes</li>
          <li>Pandemics</li>
          <li>Power outages</li>
          <li>Transportation disruptions</li>
        </ul>
      </section>

      <section>
        <h4 class="font-semibold text-gray-900  mb-1">17. Privacy</h4>
        <p>
          Your use of this website is also governed by our Privacy Policy. By using the website, you consent to the
          collection and processing of personal information in accordance with applicable privacy laws, including
          the Philippine Data Privacy Act of 2012.
        </p>
      </section>

      <section>
        <h4 class="font-semibold text-gray-900  mb-1">18. Suspension and Termination</h4>
        <p class="mb-1">We reserve the right to:</p>
        <ul class="list-disc list-inside ml-2 space-y-0.5">
          <li>Restrict access</li>
          <li>Suspend accounts</li>
          <li>Terminate services</li>
        </ul>
        <p class="mt-2">for violations of these Terms and Conditions or applicable laws.</p>
      </section>

      <section>
        <h4 class="font-semibold text-gray-900  mb-1">19. Governing Law</h4>
        <p>
          These Terms and Conditions shall be governed by and construed in accordance with the laws of the Republic
          of the Philippines. Any disputes arising from these Terms shall be subject to the exclusive jurisdiction
          of the courts of Navotas City, Philippines.
        </p>
      </section>

      <section>
        <h4 class="font-semibold text-gray-900  mb-1">20. Amendments</h4>
        <p>
          The Company reserves the right to modify these Terms and Conditions at any time. Updated versions will be
          posted on the website with a revised effective date. Continued use of the website constitutes acceptance
          of the revised Terms.
        </p>
      </section>

      <section>
        <h4 class="font-semibold text-gray-900  mb-1">21. Contact Information</h4>
        <p class="mb-2">For questions regarding these Terms and Conditions, contact:</p>
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
      <button type="button" onclick="closeModal('termsModal')"
        class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-sm hover:bg-gray-50    ">
        Close
      </button>
    </div>

  </div>
</div>

<script>
// ── Terms and Conditions modal ───────────────────────────────────────────────────────
// NOTE: openModal(id) / closeModal(id) are already declared globally in
// privacy_policy_modal.php. Do NOT redeclare them here — a second declaration
// would silently overwrite the first (or vice versa, depending on include order)
// and cause the exact same collision bug you just fixed with the sign-in modal.
// This file only needs its own backdrop-click listener, scoped to #termsModal.

document.getElementById('termsModal')?.addEventListener('click', function (e) {
  if (e.target === this) closeModal('termsModal');
});
</script>