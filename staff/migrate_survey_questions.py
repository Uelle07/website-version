import re

def migrate():
    with open('survey_questions.php', 'r', encoding='utf-8') as f:
        html = f.read()
        
    # 1. Replace PHP backend logic
    html = re.sub(r'<\?php\s+/\*\*.*?(?=<div id="surveyQuestionsView")', 
"""<?php
require_once __DIR__ . '/../config/init.php';
$surveyId = $_GET['id'] ?? '';
$pageTitle = 'Manage Questions';
require_once __DIR__ . '/../pages/header_staff.php';
?>
<script>
  window.SURVEY_ID = "<?= htmlspecialchars($surveyId) ?>";
</script>
""", html, flags=re.DOTALL)

    # 2. Replace PHP echo statements with JS data attributes or empty spans
    html = re.sub(r'<\?= count\(\$questions\) \?>', '<span id="qCountTop">0</span>', html)
    html = re.sub(r'<\?= count\(\$questions\) === 1 \? \'\' : \'s\' \?>', 's', html)
    
    html = re.sub(r'<\?php if \(\$survey\[\'status\'\] === \'active\'\): \?>', '<div id="publishStatusContainer">', html)
    html = re.sub(r'<\?php else: \?>', '</div><div id="publishFormContainer">', html)
    html = re.sub(r'<\?php endif; \?>', '</div>', html)
    
    html = re.sub(r'<\?= e\(\$survey\[\'title\'\]\) \?>', '<span id="surveyTitleDisp">Loading...</span>', html)
    html = re.sub(r'<\?= e\(\$survey\[\'description\'\] \?\: \'Define survey questions, response types, and choice options\.\'\) \?>', '<span id="surveyDescDisp">Loading...</span>', html)
    
    html = re.sub(r'<\?= date\(\'M d, Y g:i A\', strtotime\(\$survey\[\'start_date\'\]\)\) \?> &mdash; <\?= date\(\'M d, Y g:i A\', strtotime\(\$survey\[\'end_date\'\]\)\) \?>', '<span id="surveyDatesDisp">Loading...</span>', html)
    
    html = re.sub(r'<\?= count\(\$questions\) \?>', '<span class="qCountTab">0</span>', html)
    html = re.sub(r'<\?= number_format\(\$totalSurveyRespondents\) \?>', '<span id="respCountTab">0</span>', html)
    
    html = re.sub(r'<\?php if \(\!empty\(\$errors\)\): \?>.*?</div>\s*<\?php endif; \?>', '', html, flags=re.DOTALL)
    
    # 3. Replace Questions List
    # We will replace the entire inner part of #tabQuestionsContent with an empty div, and we will render the questions via JS.
    questions_content_start = html.find('<div id="tabQuestionsContent">')
    questions_content_end = html.find('<div id="tabResponsesContent"', questions_content_start)
    
    html = html[:questions_content_start] + """<div id="tabQuestionsContent">
    <div class="section-title" style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
      <div id="addQuestionBtnContainer"></div>
      <div>
        <h3 style="font-size: 1.1rem; color: var(--text-main); font-weight: 700; margin-bottom: 0.15rem;">Question List (<span class="qCountTab">0</span>)</h3>
        <p class="section-subtitle" style="margin-bottom: 0;">Configured survey items and response options</p>
      </div>
    </div>
    <div id="questionsListContainer">
        <div class="table-card text-center py-5"><i class="fa-solid fa-spinner fa-spin"></i> Loading questions...</div>
    </div>
  </div>
  """ + html[questions_content_end:]
  
    # 4. Replace Responses List
    responses_content_start = html.find('<div id="tabResponsesContent"')
    responses_content_end = html.find('<div id="tabSettingsContent"', responses_content_start)
    
    html = html[:responses_content_start] + """<div id="tabResponsesContent" style="display: none;">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
      <div>
        <h3 style="font-size: 1.5rem; font-weight: 800; color: var(--text-main); margin-bottom: 0.25rem; letter-spacing: -0.2px;">
          <span id="respCountHeader">0</span> Responses
        </h3>
        <p class="section-subtitle" style="margin-bottom: 0; font-size: 0.88rem;">Aggregated response summaries, question-level analysis, and individual answer sheets</p>
      </div>
      <div class="d-inline-flex align-items-center gap-2 flex-wrap">
        <a id="printReportLink" href="#" target="_blank" class="btn btn-secondary btn-sm" style="text-decoration: none; padding: 6px 13px; font-size: 0.82rem;">
          <i class="fa-solid fa-print me-1"></i> Print Report
        </a>
      </div>
    </div>

    <!-- Secondary Responses Sub-Tabs -->
    <div style="display: flex; justify-content: center; margin-bottom: 1.5rem;">
      <div style="display: inline-flex; gap: 0.4rem; background: rgba(255, 255, 255, 0.04); border: 1px solid var(--bg-card-border); padding: 4px; border-radius: 10px; flex-wrap: wrap; justify-content: center;">
        <button type="button" class="btn btn-sm responses-subtab-btn active-subtab" onclick="switchResponsesSubtab('summary')" style="border-radius: 7px; padding: 6px 16px; font-size: 0.84rem;">
          <i class="fa-solid fa-chart-pie me-1"></i> Summary
        </button>
        <button type="button" class="btn btn-sm responses-subtab-btn" onclick="switchResponsesSubtab('question')" style="border-radius: 7px; padding: 6px 16px; font-size: 0.84rem;">
          <i class="fa-solid fa-list-ol me-1"></i> Question
        </button>
        <button type="button" class="btn btn-sm responses-subtab-btn" onclick="switchResponsesSubtab('individual')" style="border-radius: 7px; padding: 6px 16px; font-size: 0.84rem;">
          <i class="fa-solid fa-user-check me-1"></i> Individual (<span id="individualCount">0</span>)
        </button>
      </div>
    </div>

    <div id="subtabSummaryContent">
        <div class="metrics-grid" style="margin-bottom: 1.5rem;">
            <div class="metric-card hover-glow-card"><div class="metric-icon purple"><i class="fa-solid fa-users"></i></div><div class="metric-data"><span class="metric-label">Total Respondents</span><h3 class="metric-value" id="statTotalResp">0</h3><span class="metric-subtext"><i class="fa-solid fa-check"></i> Completed submissions</span></div></div>
            <div class="metric-card hover-glow-card"><div class="metric-icon cyan"><i class="fa-solid fa-chart-pie"></i></div><div class="metric-data"><span class="metric-label">Participation Rate</span><h3 class="metric-value" id="statPartRate">0%</h3><span class="metric-subtext positive"><i class="fa-solid fa-users-line"></i> Of <span id="statTotalMem">0</span> active members</span></div></div>
            <div class="metric-card hover-glow-card"><div class="metric-icon emerald"><i class="fa-solid fa-list-check"></i></div><div class="metric-data"><span class="metric-label">Total Questions</span><h3 class="metric-value qCountTab">0</h3><span class="metric-subtext"><i class="fa-solid fa-database"></i> Live calculated items</span></div></div>
        </div>
        <div id="summaryTableContainer"></div>
    </div>

    <div id="subtabQuestionContent" style="display: none;">
        <div id="questionBreakdownContainer"></div>
    </div>

    <div id="subtabIndividualContent" style="display: none;">
        <div id="individualContainer"></div>
    </div>
  </div>
  """ + html[responses_content_end:]
  
    # 5. Replace Settings
    settings_start = html.find('<div id="tabSettingsContent"')
    settings_end = html.find('<div id="addQuestionModal"', settings_start)
    
    html = html[:settings_start] + """<div id="tabSettingsContent" style="display: none;">
    <div class="table-card" style="margin-top: 0; padding: 1.85rem 2rem; max-width: 800px; margin-bottom: 2rem;">
      <h3 style="font-size: 1.15rem; font-weight: 700; color: var(--text-main); margin-bottom: 1.25rem;"><i class="fa-solid fa-sliders text-cyan me-2"></i> Survey Settings</h3>
      <form id="editSettingsForm">
        <div class="form-grid">
          <div class="form-group full-width">
            <label>Survey Title <span style="color: #f87171;">*</span></label>
            <input type="text" name="title" id="setSurveyTitle" required>
          </div>
          <div class="form-group full-width">
            <label>Survey Status <span style="color: #f87171;">*</span></label>
            <select name="status" id="setSurveyStatus" required style="height: 44px; width: 100%; border-radius: 10px; padding: 0.6rem 0.95rem; background: var(--input-bg); border: 1px solid var(--input-border); color: var(--text-main);">
              <option value="active">Active (Published)</option>
              <option value="inactive">Inactive / Closed</option>
              <option value="draft">Draft (Unpublished)</option>
            </select>
          </div>
          <div class="form-group full-width">
            <label>Description / Instructions</label>
            <textarea name="description" id="setSurveyDesc" rows="3"></textarea>
          </div>
          <div class="form-group">
            <label>Opening Date <span style="color: #f87171;">*</span></label>
            <input type="date" name="start_date" id="setStartDate" required>
          </div>
          <div class="form-group">
            <label>Opening Time <span style="color: #f87171;">*</span></label>
            <input type="time" name="start_time" id="setStartTime" required>
          </div>
          <div class="form-group">
            <label>Closing Date <span style="color: #f87171;">*</span></label>
            <input type="date" name="end_date" id="setEndDate" required>
          </div>
          <div class="form-group">
            <label>Closing Time <span style="color: #f87171;">*</span></label>
            <input type="time" name="end_time" id="setEndTime" required>
          </div>
        </div>
        <div style="margin-top: 1.75rem; padding-top: 1.25rem; border-top: 1px solid var(--bg-card-border); display: flex; justify-content: flex-end;">
          <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-1"></i> Save Changes</button>
        </div>
      </form>
    </div>
  </div>
  """ + html[settings_end:]

    # 6. Forms in Modals
    html = re.sub(r'<form action="survey_questions\.php.*?" method="POST".*?>', '<form>', html)
    html = re.sub(r'<\?php echo htmlspecialchars.*? \?>', '', html)
    html = re.sub(r'<\?= e\(csrf_token\(\)\) \?>', '', html)
    
    html = re.sub(r'<\?php require_once __DIR__ \. \'/../pages/footer_staff\.php\'; \?>', 
    """<script src="survey_questions_app.js" type="module"></script>
<?php require_once __DIR__ . '/../pages/footer_staff.php'; ?>""", html)

    # 7. Remove the inline script that was doing things we'll handle in our module
    # We will just write our own clean script in survey_questions_app.js
    
    with open('survey_questions.php', 'w', encoding='utf-8') as f:
        f.write(html)
        
    print("Migration of HTML complete.")

if __name__ == '__main__':
    migrate()
