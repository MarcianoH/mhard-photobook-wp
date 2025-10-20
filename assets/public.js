/**
 * MHard Photobook for WordPress - Frontend Rules Engine
 * Handles conditional show/hide logic for configurator groups and options
 */

(function() {
    'use strict';

    // Parse rules data from embedded JSON
    const rulesDataEl = document.getElementById('cl-rules-data');
    if (!rulesDataEl) {
        return; // No rules defined
    }

    let rules = [];
    try {
        rules = JSON.parse(rulesDataEl.textContent);
    } catch (e) {
        console.error('Failed to parse rules data:', e);
        return;
    }

    if (!rules || rules.length === 0) {
        return; // No rules to apply
    }

    // Cache DOM elements
    const formEl = document.querySelector('.cl-form form');
    if (!formEl) {
        return;
    }

    // Get all groups and options
    const groups = formEl.querySelectorAll('.cl-group');
    const options = formEl.querySelectorAll('.cl-option');

    // Create lookup maps for faster access
    const groupsById = new Map();
    const optionsById = new Map();

    groups.forEach(group => {
        const groupId = group.getAttribute('data-group-id');
        if (groupId) {
            groupsById.set(parseInt(groupId), group);
        }
    });

    options.forEach(option => {
        const optionId = option.getAttribute('data-option-id');
        if (optionId) {
            optionsById.set(parseInt(optionId), option);
        }
    });

    /**
     * Get all selected option IDs from the form
     */
    function getSelectedOptions() {
        const selected = new Set();
        const checkedInputs = formEl.querySelectorAll('input[type="radio"]:checked, input[type="checkbox"]:checked');
        checkedInputs.forEach(input => {
            selected.add(parseInt(input.value));
        });
        return selected;
    }

    /**
     * Apply all rules based on current selections
     */
    function applyRules() {
        const selectedOptions = getSelectedOptions();

        // First, reset all groups to hidden (except those without rules)
        const groupsAffectedByRules = new Set();
        rules.forEach(rule => {
            if (rule.then_group_id) {
                groupsAffectedByRules.add(parseInt(rule.then_group_id));
            }
        });

        // Hide groups that are controlled by rules
        groupsAffectedByRules.forEach(groupId => {
            const groupEl = groupsById.get(groupId);
            if (groupEl) {
                groupEl.style.display = 'none';
                groupEl.classList.add('cl-hidden-by-rule');
            }
        });

        // Apply each rule
        rules.forEach(rule => {
            const ifGroupId = parseInt(rule.if_group_id);
            const ifOptionId = rule.if_option_id ? parseInt(rule.if_option_id) : null;
            const thenGroupId = rule.then_group_id ? parseInt(rule.then_group_id) : null;
            const thenOptionId = rule.then_option_id ? parseInt(rule.then_option_id) : null;
            const effect = rule.effect;

            // Check if condition is met
            let conditionMet = false;

            if (ifOptionId) {
                // Condition: specific option is selected
                conditionMet = selectedOptions.has(ifOptionId);
            } else {
                // Condition: any option in the group is selected
                const groupEl = groupsById.get(ifGroupId);
                if (groupEl) {
                    const groupInputs = groupEl.querySelectorAll('input[type="radio"]:checked, input[type="checkbox"]:checked');
                    conditionMet = groupInputs.length > 0;
                }
            }

            // Apply effect if condition is met
            if (conditionMet) {
                if (effect === 'show_group' && thenGroupId) {
                    const groupEl = groupsById.get(thenGroupId);
                    if (groupEl) {
                        groupEl.style.display = '';
                        groupEl.classList.remove('cl-hidden-by-rule');
                    }
                } else if (effect === 'hide_group' && thenGroupId) {
                    const groupEl = groupsById.get(thenGroupId);
                    if (groupEl) {
                        groupEl.style.display = 'none';
                        groupEl.classList.add('cl-hidden-by-rule');
                    }
                } else if (effect === 'show_option' && thenOptionId) {
                    const optionEl = optionsById.get(thenOptionId);
                    if (optionEl) {
                        optionEl.style.display = '';
                        optionEl.classList.remove('cl-hidden-by-rule');
                    }
                } else if (effect === 'hide_option' && thenOptionId) {
                    const optionEl = optionsById.get(thenOptionId);
                    if (optionEl) {
                        optionEl.style.display = 'none';
                        optionEl.classList.add('cl-hidden-by-rule');

                        // Uncheck hidden option
                        const input = optionEl.querySelector('input');
                        if (input) {
                            input.checked = false;
                        }
                    }
                }
            }
        });
    }

    /**
     * Initialize: apply rules on page load and when selections change
     */
    function init() {
        // Apply rules on initial load
        applyRules();

        // Listen to all input changes
        const inputs = formEl.querySelectorAll('input[type="radio"], input[type="checkbox"]');
        inputs.forEach(input => {
            input.addEventListener('change', applyRules);
        });
    }

    // Run when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
