import re

def process_file():
    with open('survey_questions.php', 'r', encoding='utf-8') as f:
        content = f.read()

    # The goal is to replace the PHP loops with empty container divs,
    # and add a JS module that will populate them.

    # 1. Remove PHP backend logic at the top (lines 1 to 438 roughly)
    # We will just replace from the start of the file up to the HTML
    new_top = """<?php
/**
 * staff/survey_questions.php
 * -------------------------------------------------------
 * Staff Portal — Survey Question & Choice Builder (Firebase)
 * Manage questions, choices, and question ordering for a survey.
 * -------------------------------------------------------
 */

require_once __DIR__ . '/../config/init.php';
$surveyId = $_GET['id'] ?? '';
if (!$surveyId) {
    header('Location: surveys.php');
    exit;
}

$pageTitle = 'Manage Questions';
require_once __DIR__ . '/../pages/header_staff.php';
?>
"""
    # Find the start of HTML
    html_start = content.find('<div id="surveyQuestionsView"')
    content = new_top + content[html_start:]

    # Now we need to remove PHP tags and replace them with empty containers for JS to populate.
    # We can use regex or manual replacement.

    # 2. Replace the Header block (Survey Title, status, etc)
    header_block_re = re.compile(r'<!-- Row 1: Back Button \+ Eyebrow Heading \+ Top Right Publish Button & Question Count -->.*?<!-- Divider -->', re.DOTALL)
    
    new_header_block = """<!-- Row 1: Back Button + Eyebrow Heading + Top Right Publish Button & Question Count -->
    <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; margin-bottom: 0.95rem; flex-wrap: wrap;">
      <div style="display: flex; align-items: center; gap: 0.85rem;">
        <a href="surveys.php" class="back-nav-btn" title="Back to Survey Management">
          <i class="fa-solid fa-arrow-left"></i>
        </a>
        <div style="font-size: 0.74rem; font-weight: 800; text-transform: uppercase; color: var(--primary-teal); letter-spacing: 0.8px; display: inline-flex; align-items: center; gap: 0.4rem;">
          <i class="fa-solid fa-clipboard-list"></i> QUESTIONNAIRE MANAGEMENT
        </div>
      </div>
      <div class="d-inline-flex align-items-center gap-2" id="headerQuestionStats">
        <span class="questions-count-pill"><i class="fa-solid fa-list-check"></i> <span id="hdrQCount">0 Questions</span></span>
        <div id="publishActionContainer"></div>
      </div>
    </div>
    <!-- Row 2: Survey Title -->
    <div style="margin-bottom: 1.25rem;">
      <h1 id="hdrSurveyTitle" style="font-size: 1.65rem; font-weight: 800; color: var(--text-main); margin-bottom: 0.45rem; line-height: 1.3; letter-spacing: -0.3px;">Loading...</h1>
      <p id="hdrSurveyDesc" style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 0; max-width: 820px; line-height: 1.55;">...</p>
    </div>
    <!-- Divider -->"""
    content = header_block_re.sub(new_header_block, content)

    # 3. Replace Row 3 schedule dates
    row3_re = re.compile(r'<!-- Row 3: Schedule Dates Metadata -->.*?</div>', re.DOTALL)
    content = row3_re.sub("""<!-- Row 3: Schedule Dates Metadata -->
    <div class="d-flex justify-content-end align-items-center flex-wrap gap-3">
      <span style="color: var(--text-dim); font-size: 0.82rem; display: inline-flex; align-items: center; gap: 0.35rem;" id="hdrSurveyDates">
        <i class="fa-solid fa-calendar-days" style="color: var(--primary-teal);"></i> Loading...
      </span>
    </div>""", content)

    # 4. Replace Tab Bar
    tabbar_re = re.compile(r'<!-- Navigation Tab Bar:.*?</div>\s*</div>', re.DOTALL)
    content = tabbar_re.sub("""<!-- Navigation Tab Bar -->
  <div class="questionnaire-tabnav-wrapper">
    <div class="questionnaire-tab-track">
      <button type="button" class="btn questionnaire-tab-btn active-tab" id="btnTabQuestions" onclick="switchQuestionnaireTab('questions')">
        <i class="fa-solid fa-list-check"></i> Questions (<span id="tabQCount">0</span>)
      </button>
      <button type="button" class="btn questionnaire-tab-btn" id="btnTabResponses" onclick="switchQuestionnaireTab('responses')">
        <i class="fa-solid fa-chart-column"></i> Responses (<span id="tabRCount">0</span>)
      </button>
      <button type="button" class="btn questionnaire-tab-btn" id="btnTabSettings" onclick="switchQuestionnaireTab('settings')">
        <i class="fa-solid fa-gear"></i> Settings
      </button>
    </div>
  </div>""", content)

    # 5. Remove <?php if (!empty($errors)): ?> block
    errors_re = re.compile(r'<\?php if \(!empty\(\$errors\)\): \?>.*?<\?php endif; \?>', re.DOTALL)
    content = errors_re.sub('', content)

    # 6. Tab 1: Questions Content
    tab1_start = content.find('<!-- =========================================================\n       TAB 1: QUESTIONS CONTENT (DEFAULT ACTIVE)')
    tab2_start = content.find('<!-- =========================================================\n       TAB 2: RESPONSES & ANALYTICS CONTENT')
    
    tab1_content = content[tab1_start:tab2_start]
    # Replace the whole content of Tab 1
    new_tab1 = """<!-- TAB 1: QUESTIONS CONTENT -->
  <div id="tabQuestionsContent">
    <div class="section-title" style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
      <div id="addQuestionBtnContainer"></div>
      <div>
        <h3 style="font-size: 1.1rem; color: var(--text-main); font-weight: 700; margin-bottom: 0.15rem;">Question List (<span id="qListCount">0</span>)</h3>
        <p class="section-subtitle" style="margin-bottom: 0;">Configured survey items and response options</p>
      </div>
    </div>
    <div id="questionsListContainer"></div>
  </div>
"""
    content = content[:tab1_start] + new_tab1 + content[tab2_start:]

    # 7. Tab 2: Responses Content
    tab2_start = content.find('<!-- =========================================================\n       TAB 2: RESPONSES & ANALYTICS CONTENT')
    tab3_start = content.find('<!-- =========================================================\n       TAB 3: SURVEY SETTINGS')
    
    # We need to preserve the inner structure but empty the data
    # Actually, the python script can just inject a completely rewritten file.
    pass

    with open('survey_questions.php', 'w', encoding='utf-8') as f:
        f.write(content)

if __name__ == '__main__':
    process_file()
