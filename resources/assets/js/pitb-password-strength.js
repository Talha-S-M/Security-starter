(function (global) {
    const DEFAULT_POLICY = {
        min_length: 12,
        require_uppercase: true,
        require_lowercase: true,
        require_numbers: true,
        require_symbols: true,
    };

    function parsePolicy(raw) {
        if (!raw) {
            return { ...DEFAULT_POLICY };
        }

        try {
            const parsed = typeof raw === 'string' ? JSON.parse(raw) : raw;

            return {
                min_length: Number(parsed.min_length ?? DEFAULT_POLICY.min_length),
                require_uppercase: Boolean(parsed.require_uppercase ?? DEFAULT_POLICY.require_uppercase),
                require_lowercase: Boolean(parsed.require_lowercase ?? DEFAULT_POLICY.require_lowercase),
                require_numbers: Boolean(parsed.require_numbers ?? DEFAULT_POLICY.require_numbers),
                require_symbols: Boolean(parsed.require_symbols ?? DEFAULT_POLICY.require_symbols),
            };
        } catch (error) {
            return { ...DEFAULT_POLICY };
        }
    }

    function buildRuleDefinitions(policy) {
        const rules = [
            {
                key: 'min_length',
                label: 'At least ' + policy.min_length + ' characters',
            },
        ];

        if (policy.require_uppercase && policy.require_lowercase) {
            rules.push({ key: 'mixed_case', label: 'Uppercase and lowercase letters' });
        } else if (policy.require_uppercase || policy.require_lowercase) {
            rules.push({ key: 'letters', label: 'At least one letter' });
        }

        if (policy.require_numbers) {
            rules.push({ key: 'numbers', label: 'At least one number' });
        }

        if (policy.require_symbols) {
            rules.push({ key: 'symbols', label: 'At least one symbol' });
        }

        return rules;
    }

    function evaluateRules(password, policy) {
        return {
            min_length: password.length >= policy.min_length,
            mixed_case: /[A-Z]/.test(password) && /[a-z]/.test(password),
            letters: /[A-Za-z]/.test(password),
            numbers: /[0-9]/.test(password),
            symbols: /[^A-Za-z0-9]/.test(password),
        };
    }

    function score(password, policy, checks) {
        if (!password) {
            return 0;
        }

        let value = 0;

        if (checks.min_length) {
            value += 25;
            const extra = Math.max(0, password.length - policy.min_length);
            value += Math.min(15, Math.floor(extra / 2));
        }

        if (!policy.require_uppercase || (checks.mixed_case || checks.letters)) {
            value += 15;
        }

        if (!policy.require_lowercase || (checks.mixed_case || checks.letters)) {
            value += 15;
        }

        if (!policy.require_numbers || checks.numbers) {
            value += 15;
        }

        if (!policy.require_symbols || checks.symbols) {
            value += 15;
        }

        return Math.min(100, value);
    }

    function strengthLabel(value) {
        if (value >= 81) {
            return 'strong';
        }

        if (value >= 61) {
            return 'good';
        }

        if (value >= 41) {
            return 'fair';
        }

        return 'weak';
    }

    function analyze(password, confirmation, policyInput) {
        const policy = parsePolicy(policyInput);
        const checks = evaluateRules(password, policy);
        const definitions = buildRuleDefinitions(policy);

        const rules = definitions.map(function (definition) {
            return {
                key: definition.key,
                label: definition.label,
                passed: Boolean(checks[definition.key]),
            };
        });

        if (confirmation !== null && confirmation !== undefined) {
            rules.push({
                key: 'confirmed',
                label: 'Passwords match',
                passed: password !== '' && password === confirmation,
            });
        }

        const policyValid = rules
            .filter(function (rule) { return rule.key !== 'confirmed'; })
            .every(function (rule) { return rule.passed; });

        const confirmed = confirmation === null || confirmation === undefined || rules.some(function (rule) {
            return rule.key === 'confirmed' && rule.passed;
        });

        const value = score(password, policy, checks);

        return {
            valid: policyValid && confirmed,
            strength: strengthLabel(value),
            score: value,
            rules: rules,
        };
    }

    function bind(container) {
        const policy = parsePolicy(container.dataset.policy);
        const passwordInput = document.getElementById(container.dataset.passwordId);

        if (!passwordInput) {
            return;
        }

        const confirmationInput = container.dataset.confirmationId
            ? document.getElementById(container.dataset.confirmationId)
            : null;

        const fill = document.getElementById(container.dataset.meterId);
        const label = container.querySelector('[data-strength-label]');
        const rulesList = container.querySelector('[data-strength-rules]');
        const status = container.querySelector('[data-strength-status]');
        const form = passwordInput.closest('form');
        const submit = form ? form.querySelector('[type="submit"]') : null;

        function show() {
            container.hidden = false;
        }

        function hide() {
            container.hidden = true;
        }

        function update() {
            const result = analyze(
                passwordInput.value,
                confirmationInput ? confirmationInput.value : null,
                policy
            );

            fill.style.width = result.score + '%';
            fill.dataset.strength = result.strength;
            label.textContent = 'Password strength: ' + result.strength.charAt(0).toUpperCase() + result.strength.slice(1);

            rulesList.innerHTML = result.rules.map(function (rule) {
                return '<li class="' + (rule.passed ? 'is-passed' : 'is-failed') + '">' + rule.label + '</li>';
            }).join('');

            if (passwordInput.value === '') {
                status.hidden = true;
            } else {
                status.hidden = false;
                status.textContent = result.valid
                    ? 'Password meets policy requirements.'
                    : 'Password does not meet all requirements yet.';
                status.className = 'pitb-password-strength__status ' + (result.valid ? 'is-valid' : 'is-invalid');
            }

            if (submit) {
                submit.disabled = !result.valid;
            }

            container.dispatchEvent(new CustomEvent('pitb-password-strength', { detail: result }));
        }

        passwordInput.addEventListener('focus', function () {
            show();
            update();
        });

        passwordInput.addEventListener('blur', hide);

        passwordInput.addEventListener('input', update);

        if (confirmationInput) {
            confirmationInput.addEventListener('input', update);
        }

        if (submit) {
            submit.disabled = true;
        }

        update();
    }

    function init(root) {
        const scope = root || document;
        scope.querySelectorAll('[data-pitb-password-strength]').forEach(bind);
    }

    const api = {
        analyze: analyze,
        bind: bind,
        init: init,
        buildRuleDefinitions: buildRuleDefinitions,
        parsePolicy: parsePolicy,
    };

    global.PitbPasswordStrength = api;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { init(); });
    } else {
        init();
    }
})(window);
