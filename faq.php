<?php
/**
 * StudentHub Portal - Help & Frequently Asked Questions (FAQ)
 * Practical 4: Collapsible Accordion & DOM Manipulation
 * Practical 6: External JSON Data Rendering & Search
 */

$page_title = "Help & Frequently Asked Questions";
require_once __DIR__ . '/includes/header.php';

// Load FAQs from external JSON file (Practical 6)
$json_path = DATA_PATH . '/faqs.json';
$faqs = file_exists($json_path) ? json_decode(file_get_contents($json_path), true) : [];
if (!is_array($faqs)) $faqs = [];

// Group by category
$categories = [];
foreach ($faqs as $faq) {
    $cat = $faq['category'] ?? 'General';
    $categories[$cat][] = $faq;
}
?>

<!-- Breadcrumbs (Practical 2) -->
<nav aria-label="breadcrumb" class="mb-4">
  <ol class="breadcrumb py-2 px-3 bg-body-tertiary rounded-3 shadow-sm">
    <li class="breadcrumb-item"><a href="index.php"><i class="bi bi-house-door me-1"></i>Home</a></li>
    <li class="breadcrumb-item active" aria-current="page">Help &amp; FAQ</li>
  </ol>
</nav>

<div class="row justify-content-center mb-5">
  <div class="col-lg-9">
    <div class="text-center mb-4">
      <span class="badge bg-primary-subtle text-primary mb-2">Frequently Asked Questions</span>
      <h1 class="h2 fw-bold text-primary mb-2">How Can We Help You?</h1>
      <p class="text-muted">Find answers to common questions about accounts, event registrations, security, and accessibility.</p>
      
      <!-- Real-time Client-Side Search (Practical 4 & 6) -->
      <div class="max-w-md mx-auto mt-3" style="max-width: 460px;">
        <div class="input-group shadow-sm">
          <span class="input-group-text bg-body"><i class="bi bi-search"></i></span>
          <input type="text" id="faqSearchInput" class="form-control" placeholder="Search questions (e.g. timeout, event, security)...">
        </div>
      </div>
    </div>

    <!-- Collapsible Accordion (Practical 4) -->
    <div id="faqAccordionContainer">
      <?php foreach ($categories as $category_name => $items): ?>
        <div class="faq-category-block mb-4" data-category="<?php echo e($category_name); ?>">
          <h2 class="fs-5 fw-bold text-primary border-bottom pb-2 mb-3">
            <i class="bi bi-bookmark-fill me-2"></i><?php echo e($category_name); ?> Questions
          </h2>

          <?php foreach ($items as $idx => $faq): ?>
            <div class="faq-item" data-question="<?php echo e(strtolower($faq['question'])); ?>" data-answer="<?php echo e(strtolower($faq['answer'])); ?>">
              <button type="button" class="faq-question" aria-expanded="false" aria-controls="faq-ans-<?php echo $faq['id']; ?>">
                <span><?php echo e($faq['question']); ?></span>
                <i class="bi bi-chevron-down text-muted transition-transform"></i>
              </button>
              <div id="faq-ans-<?php echo $faq['id']; ?>" class="faq-answer">
                <p class="mb-0"><?php echo e($faq['answer']); ?></p>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endforeach; ?>
    </div>

    <div id="noFaqMatch" class="text-center py-5 d-none">
      <div class="display-6 text-muted mb-3"><i class="bi bi-search"></i></div>
      <h3 class="fs-5 fw-bold">No Matching Questions Found</h3>
      <p class="text-muted small">Try searching for other keywords, or visit our <a href="contact.php">Contact Support</a> page.</p>
    </div>
  </div>
</div>

<!-- Dynamic FAQ Filtering Script (Practical 4 DOM Manipulation) -->
<script>
document.addEventListener('DOMContentLoaded', () => {
  const searchInput = document.getElementById('faqSearchInput');
  const items = document.querySelectorAll('.faq-item');
  const blocks = document.querySelectorAll('.faq-category-block');
  const noMatch = document.getElementById('noFaqMatch');

  if (searchInput) {
    searchInput.addEventListener('input', () => {
      const q = searchInput.value.toLowerCase().trim();
      let matchCount = 0;

      items.forEach(item => {
        const question = item.getAttribute('data-question') || '';
        const answer = item.getAttribute('data-answer') || '';
        const matches = !q || question.includes(q) || answer.includes(q);

        item.style.display = matches ? 'block' : 'none';
        if (matches) matchCount++;
      });

      // Hide category headings if all items in category are hidden
      blocks.forEach(block => {
        const visibleItems = block.querySelectorAll('.faq-item:not([style*="display: none"])');
        block.style.display = visibleItems.length > 0 ? 'block' : 'none';
      });

      if (noMatch) {
        noMatch.classList.toggle('d-none', matchCount > 0);
      }
    });
  }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
