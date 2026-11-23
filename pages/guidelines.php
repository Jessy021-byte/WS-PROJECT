<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();

$pageTitle = 'Guidelines · CrossRent';
require __DIR__ . '/../includes/header.php';
?>

<div class="form-container glass-panel" style="padding: 30px; margin-top: 20px;">

  <h1 style="font-size: 24px; font-weight: 700; margin-bottom: 6px;">Community Guidelines</h1>
  <p class="text-muted" style="margin-bottom: 28px;">Please read and follow these rules to keep CrossRent safe and fair for everyone at HCDC.</p>

  <div class="guideline-section">
    <div class="guideline-icon"><i class="fa-solid fa-user-check"></i></div>
    <div>
      <h3>1. HCDC Members Only</h3>
      <p>CrossRent is exclusively for Holy Cross of Davao College students, faculty, and staff. Only accounts registered with an official <strong>@hcdc.edu.ph</strong> email are allowed.</p>
    </div>
  </div>

  <div class="guideline-section">
    <div class="guideline-icon"><i class="fa-solid fa-box-open"></i></div>
    <div>
      <h3>2. Accurate Listings</h3>
      <p>Always provide honest and accurate descriptions of your items. Include clear photos, correct pricing, and the actual condition of the item. Misleading listings will be removed.</p>
    </div>
  </div>

  <div class="guideline-section">
    <div class="guideline-icon"><i class="fa-solid fa-handshake"></i></div>
    <div>
      <h3>3. Respect Agreements</h3>
      <p>Once a rental is agreed upon, both parties must honor the terms. Cancellations should be communicated as early as possible. Repeated no-shows may result in account suspension.</p>
    </div>
  </div>

  <div class="guideline-section">
    <div class="guideline-icon"><i class="fa-solid fa-shield-halved"></i></div>
    <div>
      <h3>4. Take Care of Rented Items</h3>
      <p>Renters are responsible for returning items in the same condition they were received. Any damage beyond normal wear must be reported and settled with the owner.</p>
    </div>
  </div>

  <div class="guideline-section">
    <div class="guideline-icon"><i class="fa-solid fa-ban"></i></div>
    <div>
      <h3>5. Prohibited Items</h3>
      <p>The following are strictly not allowed on CrossRent:</p>
      <ul class="guideline-list">
        <li>Illegal, stolen, or counterfeit goods</li>
        <li>Weapons or dangerous materials</li>
        <li>Alcohol, drugs, or controlled substances</li>
        <li>Explicit or inappropriate content</li>
      </ul>
    </div>
  </div>

  <div class="guideline-section">
    <div class="guideline-icon"><i class="fa-solid fa-comments"></i></div>
    <div>
      <h3>6. Respectful Communication</h3>
      <p>Be polite and professional in all messages. Harassment, threats, or offensive language toward other users will not be tolerated and may result in a permanent ban.</p>
    </div>
  </div>

  <div class="guideline-section">
    <div class="guideline-icon"><i class="fa-solid fa-lock"></i></div>
    <div>
      <h3>7. Protect Your Privacy</h3>
      <p>Never share your password with anyone. Do not post personal information such as home addresses or financial details in listings or messages.</p>
    </div>
  </div>

  <div class="guideline-section">
    <div class="guideline-icon"><i class="fa-solid fa-flag"></i></div>
    <div>
      <h3>8. Report Violations</h3>
      <p>If you encounter a listing or user that violates these guidelines, please report it to the admin immediately. Help us keep the community safe.</p>
    </div>
  </div>

</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
