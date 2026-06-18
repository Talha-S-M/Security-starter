(function (global) {
    const CHARSETS = {
        upper: 'ABCDEFGHJKLMNPQRSTUVWXYZ',
        lower: 'abcdefghjkmnpqrstuvwxyz',
        numbers: '23456789',
        symbols: '!@#$%&*?',
    };

    function parsePolicy(raw) {
        if (global.PitbPasswordStrength && typeof global.PitbPasswordStrength.parsePolicy === 'function') {
            return global.PitbPasswordStrength.parsePolicy(raw);
        }

        try {
            return typeof raw === 'string' ? JSON.parse(raw) : (raw || {});
        } catch (error) {
            return {};
        }
    }

    function generatePassword(policy) {
        const resolved = parsePolicy(policy);
        const length = Math.max(Number(resolved.min_length || 12), 12);
        let required = '';
        let pool = '';

        if (resolved.require_uppercase) {
            required += CHARSETS.upper[Math.floor(Math.random() * CHARSETS.upper.length)];
            pool += CHARSETS.upper;
        }

        if (resolved.require_lowercase) {
            required += CHARSETS.lower[Math.floor(Math.random() * CHARSETS.lower.length)];
            pool += CHARSETS.lower;
        }

        if (resolved.require_numbers) {
            required += CHARSETS.numbers[Math.floor(Math.random() * CHARSETS.numbers.length)];
            pool += CHARSETS.numbers;
        }

        if (resolved.require_symbols) {
            required += CHARSETS.symbols[Math.floor(Math.random() * CHARSETS.symbols.length)];
            pool += CHARSETS.symbols;
        }

        if (!pool) {
            pool = CHARSETS.upper + CHARSETS.lower + CHARSETS.numbers + CHARSETS.symbols;
        }

        let password = required;

        while (password.length < length) {
            password += pool[Math.floor(Math.random() * pool.length)];
        }

        return password.split('').sort(function () {
            return Math.random() - 0.5;
        }).join('');
    }

    function buildPassword(policy) {
        const resolved = parsePolicy(policy);

        if (!global.PitbPasswordStrength || typeof global.PitbPasswordStrength.analyze !== 'function') {
            return generatePassword(resolved);
        }

        let candidate = generatePassword(resolved);
        let attempts = 0;

        while (attempts < 5 && !global.PitbPasswordStrength.analyze(candidate, resolved).valid) {
            candidate = generatePassword(resolved);
            attempts++;
        }

        return candidate;
    }

    function copyText(text) {
        if (!text) {
            return Promise.reject(new Error('Nothing to copy'));
        }

        if (navigator.clipboard && window.isSecureContext) {
            return navigator.clipboard.writeText(text);
        }

        return new Promise(function (resolve, reject) {
            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.setAttribute('readonly', '');
            textarea.style.position = 'absolute';
            textarea.style.left = '-9999px';
            document.body.appendChild(textarea);
            textarea.select();

            try {
                document.execCommand('copy');
                resolve();
            } catch (error) {
                reject(error);
            } finally {
                document.body.removeChild(textarea);
            }
        });
    }

    function setPreview(preview, valueEl, value) {
        if (!value) {
            preview.hidden = true;
            valueEl.textContent = '';

            return;
        }

        preview.hidden = false;
        valueEl.textContent = value;
    }

    function flashCopied(preview, hintEl) {
        if (!hintEl) {
            return;
        }

        const original = hintEl.textContent;
        hintEl.textContent = 'Copied!';
        preview.classList.add('is-copied');

        window.setTimeout(function () {
            hintEl.textContent = original;
            preview.classList.remove('is-copied');
        }, 1600);
    }

    function bindPreview(preview) {
        const passwordId = preview.dataset.passwordId;
        const passwordInput = document.getElementById(passwordId);
        const valueEl = preview.querySelector('[data-pitb-password-preview-value]');
        const hintEl = preview.querySelector('[data-pitb-password-copy-hint]');

        if (!passwordInput || !valueEl) {
            return;
        }

        function copyPassword() {
            const value = passwordInput.value;

            if (!value) {
                return;
            }

            copyText(value).then(function () {
                flashCopied(preview, hintEl);
            }).catch(function () {
                if (hintEl) {
                    hintEl.textContent = 'Copy failed';
                }
            });
        }

        passwordInput.addEventListener('input', function () {
            setPreview(preview, valueEl, passwordInput.value);
        });

        preview.addEventListener('click', copyPassword);
        preview.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                copyPassword();
            }
        });

        setPreview(preview, valueEl, passwordInput.value);
    }

    function bindGenerate(button) {
        button.addEventListener('click', function () {
            const policy = parsePolicy(button.dataset.policy || '{}');
            const passwordInput = document.getElementById(button.dataset.passwordId);
            const confirmationInput = document.getElementById(button.dataset.confirmationId);
            const preview = document.querySelector(
                '[data-pitb-password-preview][data-password-id="' + button.dataset.passwordId + '"]'
            );

            if (!passwordInput || !confirmationInput) {
                return;
            }

            const password = buildPassword(policy);

            passwordInput.value = password;
            confirmationInput.value = password;
            passwordInput.dispatchEvent(new Event('input', { bubbles: true }));

            if (preview) {
                const valueEl = preview.querySelector('[data-pitb-password-preview-value]');
                setPreview(preview, valueEl, password);
            }
        });
    }

    function init(root) {
        const scope = root || document;

        scope.querySelectorAll('[data-pitb-password-preview]').forEach(bindPreview);
        scope.querySelectorAll('[data-pitb-generate-password]').forEach(bindGenerate);
    }

    const api = {
        buildPassword: buildPassword,
        copyText: copyText,
        generatePassword: generatePassword,
        init: init,
        parsePolicy: parsePolicy,
    };

    global.PitbTemporaryPassword = api;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            init();
        });
    } else {
        init();
    }
})(window);
