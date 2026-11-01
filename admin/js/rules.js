function toggleRuleEdit(ruleId) {
    const panel = document.getElementById("edit-rule-" + ruleId);

    if (panel) {
        panel.hidden = !panel.hidden;
    }
}