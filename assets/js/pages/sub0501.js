/**
 * sub0501 contact form interactions
 */
(function ($) {
    'use strict';

    function resolveLang() {
        var docLang = (document.documentElement && document.documentElement.lang) ? document.documentElement.lang : '';
        if (docLang) return docLang.toLowerCase();
        var match = location.pathname.match(/\/(ko|en|ja|fr|mn)\//i);
        return match ? match[1].toLowerCase() : '';
    }

    var I18N = {
        ko: {
            fileExists: '이미 {name} 파일이 존재합니다.',
            fileRemoveConfirm: '파일을 삭제할까요?',
            fileRemoveLabel: '파일 삭제',
            submitError: '문의 접수 중 오류가 발생했습니다. 잠시 후 다시 시도해 주세요.',
            submitSuccess: '문의가 접수되었습니다.',
            inquiryRequired: '문의 유형을 선택해 주세요.',
            companyRequired: '기업 및 소속명을 입력해 주세요.',
            companyLength: '기업 및 소속명은 2~100자로 입력해 주세요.',
            managerRequired: '담당자 성명/직급을 입력해 주세요.',
            managerLength: '담당자 정보는 2~60자로 입력해 주세요.',
            countryRequired: '국가를 입력해 주세요.',
            countryLength: '국가는 2~60자로 입력해 주세요.',
            emailRequired: '이메일을 입력해 주세요.',
            emailTooLong: '이메일이 너무 깁니다.',
            emailInvalid: '이메일 형식이 올바르지 않습니다.',
            subjectRequired: '제목을 입력해 주세요.',
            subjectLength: '제목은 3~150자로 입력해 주세요.',
            messageRequired: '문의 내용을 입력해 주세요.',
            messageLength: '문의 내용은 10~5000자로 입력해 주세요.',
            privacyRequired: '개인정보 수집 및 이용에 동의해 주세요.'
        },
        en: {
            fileExists: 'File already added: {name}.',
            fileRemoveConfirm: 'Remove this file?',
            fileRemoveLabel: 'Remove file',
            submitError: 'There was an error while submitting. Please try again.',
            submitSuccess: 'Your inquiry has been received.',
            inquiryRequired: 'Please select an inquiry type.',
            companyRequired: 'Please enter a company or organization.',
            companyLength: 'Company/organization must be 2-100 characters.',
            managerRequired: 'Please enter contact name and position.',
            managerLength: 'Contact info must be 2-60 characters.',
            countryRequired: 'Please enter a country.',
            countryLength: 'Country must be 2-60 characters.',
            emailRequired: 'Please enter an email.',
            emailTooLong: 'Email is too long.',
            emailInvalid: 'Invalid email format.',
            subjectRequired: 'Please enter a subject.',
            subjectLength: 'Subject must be 3-150 characters.',
            messageRequired: 'Please enter a message.',
            messageLength: 'Message must be 10-5000 characters.',
            privacyRequired: 'Please agree to the privacy policy.'
        },
        fr: {
            fileExists: 'Un fichier du meme nom existe deja: {name}.',
            fileRemoveConfirm: 'Supprimer ce fichier?',
            fileRemoveLabel: 'Supprimer le fichier',
            submitError: 'Erreur lors de l\'envoi. Veuillez reessayer.',
            submitSuccess: 'Votre demande a bien ete recue.',
            inquiryRequired: 'Veuillez choisir un type de demande.',
            companyRequired: 'Veuillez saisir la societe ou l\'organisation.',
            companyLength: 'Societe/organisation: 2 a 100 caracteres.',
            managerRequired: 'Veuillez saisir le nom et la fonction.',
            managerLength: 'Nom/fonction: 2 a 60 caracteres.',
            countryRequired: 'Veuillez saisir le pays.',
            countryLength: 'Pays: 2 a 60 caracteres.',
            emailRequired: 'Veuillez saisir l\'email.',
            emailTooLong: 'Email trop long.',
            emailInvalid: 'Format d\'email invalide.',
            subjectRequired: 'Veuillez saisir l\'objet.',
            subjectLength: 'Objet: 3 a 150 caracteres.',
            messageRequired: 'Veuillez saisir le message.',
            messageLength: 'Message: 10 a 5000 caracteres.',
            privacyRequired: 'Veuillez accepter la politique de confidentialite.'
        },
        ja: {
            fileExists: '同じ名前のファイルがあります: {name}',
            fileRemoveConfirm: 'このファイルを削除しますか?',
            fileRemoveLabel: 'ファイル削除',
            submitError: '送信中にエラーが発生しました。もう一度お試しください。',
            submitSuccess: 'お問い合わせを受け付けました。',
            inquiryRequired: '問い合わせ種別を選択してください。',
            companyRequired: '会社/所属を入力してください。',
            companyLength: '会社/所属は2〜100文字です。',
            managerRequired: '担当者名・役職を入力してください。',
            managerLength: '担当者情報は2〜60文字です。',
            countryRequired: '国名を入力してください。',
            countryLength: '国名は2〜60文字です。',
            emailRequired: 'メールを入力してください。',
            emailTooLong: 'メールが長すぎます。',
            emailInvalid: 'メール形式が正しくありません。',
            subjectRequired: '件名を入力してください。',
            subjectLength: '件名は3〜150文字です。',
            messageRequired: '内容を入力してください。',
            messageLength: '内容は10〜5000文字です。',
            privacyRequired: '個人情報の取扱いに同意してください。'
        },
        mn: {
            fileExists: 'Ижил нэртэй файл байна: {name}.',
            fileRemoveConfirm: 'Файлыг устгах уу?',
            fileRemoveLabel: 'Файл устгах',
            submitError: 'Илгээх үед алдаа гарлаа. Дахин оролдоно уу.',
            submitSuccess: 'Таны хүсэлтийг хүлээн авлаа.',
            inquiryRequired: 'Асуудлын төрлөө сонгоно уу.',
            companyRequired: 'Байгууллагын нэр оруулна уу.',
            companyLength: 'Байгууллага 2-100 тэмдэгт байна.',
            managerRequired: 'Хариуцагч нэр, албан тушаал оруулна уу.',
            managerLength: 'Хариуцагч 2-60 тэмдэгт байна.',
            countryRequired: 'Улс оруулна уу.',
            countryLength: 'Улс 2-60 тэмдэгт байна.',
            emailRequired: 'Имэйл оруулна уу.',
            emailTooLong: 'Имэйл хэт урт байна.',
            emailInvalid: 'Имэйл формат буруу.',
            subjectRequired: 'Гарчиг оруулна уу.',
            subjectLength: 'Гарчиг 3-150 тэмдэгт байна.',
            messageRequired: 'Агуулга оруулна уу.',
            messageLength: 'Агуулга 10-5000 тэмдэгт байна.',
            privacyRequired: 'Хувийн мэдээллийн нөхцөлд зөвшөөрнө үү.'
        }
    };

    var LANG = resolveLang();

    function t(key, vars) {
        var dict = I18N[LANG] || I18N.en;
        var value = (dict && dict[key]) || (I18N.en && I18N.en[key]) || key;
        if (!vars) return value;
        return Object.keys(vars).reduce(function (text, k) {
            return text.replace(new RegExp('\\{' + k + '\\}', 'g'), vars[k]);
        }, value);
    }

    function initFileAttach($root) {
        var $fileBtn = $root.find('.filebtn');
        var $fileInput = $root.find('.fileinput');
        var $filesWrap = $root.find('.files');
        if (!$fileBtn.length || !$fileInput.length || !$filesWrap.length) return null;

        var fileList = [];

        function syncInput() {
            var input = $fileInput.get(0);
            if (!input) return;
            var data = new DataTransfer();
            fileList.forEach(function (file) {
                data.items.add(file);
            });
            input.files = data.files;
        }

        function renderFiles() {
            $filesWrap.empty();
            if (!fileList.length) {
                $filesWrap.addClass('empty');
                return;
            }

            $filesWrap.removeClass('empty');
            fileList.forEach(function (file, idx) {
                var $item = $('<div>', { class: 'file' });
                var $name = $('<span>', { class: 'filename', text: file.name });
                var $remove = $('<i>', {
                    class: 'remove',
                    role: 'button',
                    tabindex: 0,
                    'aria-label': t('fileRemoveLabel'),
                    'data-index': idx
                });

                $item.append($name, $remove);
                $filesWrap.append($item);
            });
        }

        function addFiles(files) {
            var added = false;
            files.forEach(function (file) {
                var exists = fileList.some(function (existing) {
                    return existing.name === file.name;
                });
                if (exists) {
                    window.alert(t('fileExists', { name: file.name }));
                    return;
                }
                fileList.push(file);
                added = true;
            });

            if (added) {
                renderFiles();
                syncInput();
            }
        }

        $fileBtn.on('click', function () {
            $fileInput.trigger('click');
        });

        $fileInput.on('change', function (event) {
            var input = event.target;
            var files = Array.prototype.slice.call(input.files || []);
            if (!files.length) return;
            addFiles(files);
            input.value = '';
        });

        $filesWrap.on('click', '.remove', function () {
            var idx = parseInt($(this).attr('data-index'), 10);
            if (Number.isNaN(idx)) return;
            if (!window.confirm(t('fileRemoveConfirm'))) return;
            fileList.splice(idx, 1);
            renderFiles();
            syncInput();
        });

        $filesWrap.on('keydown', '.remove', function (event) {
            if (event.key !== 'Enter' && event.key !== ' ') return;
            event.preventDefault();
            $(this).trigger('click');
        });

        function resetFiles() {
            fileList = [];
            renderFiles();
            syncInput();
        }

        renderFiles();

        return {
            reset: resetFiles,
            getFiles: function () {
                return fileList.slice();
            }
        };
    }

    function initPolicyModal($root) {
        var $policy = $root.find('.policy');
        var $policyOpen = $root.find('.link');
        var $policyClose = $root.find('.policy .close');
        if (!$policy.length || !$policyOpen.length) return;

        var prevBodyOverflow = '';
        var prevHtmlOverflow = '';

        function openPolicy() {
            $policy.addClass('open').attr('aria-hidden', 'false');
            prevBodyOverflow = $('body').css('overflow');
            prevHtmlOverflow = $('html').css('overflow');
            $('body').css('overflow', 'hidden');
            $('html').css('overflow', 'hidden');
            if (window.scrollInstance && typeof window.scrollInstance.stop === 'function') {
                window.scrollInstance.stop();
            }
            $policyClose.blur();
            $policyOpen.blur();
        }

        function closePolicy() {
            $policy.removeClass('open').attr('aria-hidden', 'true');
            $('body').css('overflow', prevBodyOverflow || '');
            $('html').css('overflow', prevHtmlOverflow || '');
            if (window.scrollInstance && typeof window.scrollInstance.start === 'function') {
                window.scrollInstance.start();
            }
            $policyClose.blur();
        }

        $policyOpen.on('click', openPolicy);
        if ($policyClose.length) {
            $policyClose.on('click', closePolicy);
        }

        $policy.on('click', function (event) {
            if (event.target === $policy.get(0)) {
                closePolicy();
            }
        });

        $(document).on('keydown', function (event) {
            if (event.key === 'Escape') {
                closePolicy();
            }
        });
    }

    function resolveBbsUrl() {
        if (window.g5_bbs_url) return window.g5_bbs_url;
        var base = (typeof resolveSiteBasePath === 'function') ? resolveSiteBasePath() : '';
        if (typeof joinPath === 'function') return joinPath(base, '/bbs');
        if (base) return base.replace(/\/+$/, '') + '/bbs';
        return '/bbs';
    }

    function extractAlertMessage(html) {
        if (!html || typeof html !== 'string') return '';
        var match = html.match(/alert\(['"]([^'"]+)/);
        if (!match) return '';
        return match[1].replace(/\\n/g, '\n');
    }
    /* UI helpers for inline errors and messages */
    function clearFieldError($el) {
        if (!$el || !$el.length) return;
        $el.removeClass('has-error');
        var $wrapper = $el.closest('.field-row, .options-row, .agree');
        if ($wrapper && $wrapper.length) {
            $wrapper.removeClass('has-error');
            $wrapper.find('.field-error').remove();
            return;
        }
        // fallback
        $el.find('.field-error').remove();
        $el.next('.field-error').remove();
    }

    function showFieldErrorFor($el, message) {
        if (!$el || !$el.length) return;
        clearFieldError($el);
        $el.addClass('has-error');
        var $err = $('<div>', { class: 'field-error', text: message, role: 'alert' });
        var $wrapper = $el.closest('.field-row, .options-row, .agree');
        if ($wrapper && $wrapper.length) {
            $wrapper.addClass('has-error');
            if ($wrapper.hasClass('field-row')) {
                $el.after($err);
            } else if ($wrapper.hasClass('options-row')) {
                $wrapper.append($err);
            } else if ($wrapper.hasClass('agree')) {
                $wrapper.append($err);
            } else {
                $el.after($err);
            }
            return;
        }
        // fallback to previous behavior
        if ($el.hasClass('options')) $el.after($err);
        else if ($el.hasClass('agree')) $el.append($err);
        else $el.after($err);
    }

    function clearAllErrors($form) {
        $form.find('.has-error').removeClass('has-error');
        $form.find('.field-error').remove();
        $form.find('.form-error, .form-success').remove();
    }

    function showFormError($form, message) {
        clearFormMessages($form);
        var $err = $('<div>', { class: 'form-error', role: 'alert' });
        var text = '';
        if (Array.isArray(message)) {
            var $list = $('<ul>');
            message.forEach(function (m) {
                $list.append($('<li>').text(m));
            });
            $err.append($list);
            text = message.join('\n');
        } else {
            $err.text(message);
            text = message;
        }
        $form.prepend($err);
        // also show a blocking alert for global form errors
        try { window.alert(text || t('submitError')); } catch (e) { /* silent */ }
    }

    function showFormSuccess($form, message) {
        clearFormMessages($form);
        // show success as an alert only (no in-page element)
        try { window.alert(message || t('submitSuccess')); } catch (e) { /* silent */ }
    }

    function clearFormMessages($form) {
        $form.find('.form-error, .form-success').remove();
    }

    function scrollToAndFocus($el) {
        if (!$el || !$el.length) return;
        try {
            gsap.to(window, { duration: 0.8, ease: 'power2.out', scrollTo: { y: $el.get(0), offsetY: 80 } });
        } catch (e) {
            $('html, body').animate({ scrollTop: Math.max(0, $el.offset().top - 80) }, 800);
        }
        setTimeout(function () { 
            var $focusEl = $el.find('input, textarea, select, [role="button"]').first();
            if ($focusEl && $focusEl.length) $focusEl.focus({ preventScroll: true });
        }, 850);
    }
    function initInquiryForm($root, fileControl) {
        var $form = $root.find('form.form');
        if (!$form.length) return;
        try { if ($form.get(0)) { $form.get(0).noValidate = true; $form.attr('novalidate','novalidate'); } } catch (e) { }

        var $submitBtn = $form.find('.submit');
        var submitting = false;

        function getFieldValue(name) {
            var $el = $form.find('[name="' + name + '"]');
            return $el.length ? $.trim($el.val()) : '';
        }

        function getSelectedInquiry() {
            var $checked = $form.find('input[name="inquiry"]:checked');
            if (!$checked.length) return null;
            var value = $checked.val();
            var label = $.trim($checked.closest('.option').text());
            return { value: value, label: label };
        }

        function validateEmail(email) {
            if (!email || typeof email !== 'string') return false;
            email = email.trim();
            // prefer using browser native email validation where possible
            try {
                var i = document.createElement('input');
                i.type = 'email';
                i.value = email;
                if (typeof i.checkValidity === 'function') {
                    var ok = i.checkValidity();
                    if (!ok) {
                        // debug info for unexpected rejects
                        if (window && window.console && typeof console.debug === 'function') {
                            console.debug('validateEmail: native check failed for', email, Array.from(email).map(function(c){ return c.charCodeAt(0); }));
                        }
                    }
                    return ok;
                }
            } catch (e) {}
            // fallback: simple regex (ensure there's an @ and a dot after it)
            var at = email.indexOf('@');
            if (at < 1) return false;
            var dot = email.lastIndexOf('.');
            if (dot < at + 2) return false;
            // basic allowed chars check
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        }

        function buildContent(data) {
            var lines = [];
            lines.push('문의 유형: ' + data.typeLabel);
            lines.push('기업/소속명: ' + data.company);
            lines.push('담당자 성명/직급: ' + data.manager);
            lines.push('국가: ' + data.country);
            lines.push('이메일: ' + data.email);
            lines.push('');
            lines.push('문의 내용:');
            lines.push(data.message);
            return lines.join('\\n');
        }

        function buildPassword() {
            return Math.random().toString(36).slice(2) + Date.now().toString(36);
        }

        function setSubmitting(state) {
            submitting = state;
            if ($submitBtn.length) {
                $submitBtn.prop('disabled', state);
                $submitBtn.toggleClass('is-loading', state);
            }
        }

        // clear errors when user interacts with fields
        $form.on('change', 'input[name="inquiry"]', function () {
            // clear inquiry options errors
            clearFieldError($form.find('.options'));
            $form.find('.options-row').removeClass('has-error');
        });

        $form.on('input change', 'input[name="company"], input[name="manager"], input[name="country"], input[name="email"], input[name="subject"], textarea[name="message"]', function () {
            var $row = $(this).closest('.field-row');
            if ($row && $row.length) {
                clearFieldError($row);
            } else {
                clearFieldError($(this).closest('.field'));
            }
        });

        $form.on('change', 'input[name="privacy"]', function () {
            var $agree = $(this).closest('.agree');
            clearFieldError($agree);
            $agree.removeClass('has-error');
        });

        $form.on('submit', function (event) {
            event.preventDefault();
            if (submitting) return;

            clearAllErrors($form);

            var inquiry = getSelectedInquiry();
            var $inquiryWrap = $form.find('.options');
            var company = getFieldValue('company');
            var $companyField = $form.find('[name="company"]').closest('.field');
            var manager = getFieldValue('manager');
            var $managerField = $form.find('[name="manager"]').closest('.field');
            var country = getFieldValue('country');
            var $countryField = $form.find('[name="country"]').closest('.field');
            var email = getFieldValue('email');
            var $emailField = $form.find('[name="email"]').closest('.field');
            var subject = getFieldValue('subject');
            var $subjectField = $form.find('[name="subject"]').closest('.field');
            var message = getFieldValue('message');
            var $messageField = $form.find('[name="message"]').closest('.field');
            var $privacyInput = $form.find('input[name="privacy"]');
            var $privacyWrap = $privacyInput.closest('.agree');
            var privacy = $privacyInput.is(':checked');
            var successMessage = $form.data('successMessage') || t('submitSuccess');

            var fieldErrors = {};
            // basic selection
            if (!inquiry) fieldErrors['inquiry'] = t('inquiryRequired');
            // length rules
            function betweenLen(str, min, max) { return typeof str === 'string' && str.length >= min && str.length <= max; }
            if (!company) fieldErrors['company'] = t('companyRequired');
            else if (!betweenLen(company, 2, 100)) fieldErrors['company'] = t('companyLength');
            if (!manager) fieldErrors['manager'] = t('managerRequired');
            else if (!betweenLen(manager, 2, 60)) fieldErrors['manager'] = t('managerLength');
            if (!country) fieldErrors['country'] = t('countryRequired');
            else if (!betweenLen(country, 2, 60)) fieldErrors['country'] = t('countryLength');
            if (!email) fieldErrors['email'] = t('emailRequired');
            else if (email.length > 254) fieldErrors['email'] = t('emailTooLong');
            else if (!validateEmail(email)) fieldErrors['email'] = t('emailInvalid');
            if (!subject) fieldErrors['subject'] = t('subjectRequired');
            else if (!betweenLen(subject, 3, 150)) fieldErrors['subject'] = t('subjectLength');
            if (!message) fieldErrors['message'] = t('messageRequired');
            else if (!betweenLen(message, 10, 5000)) fieldErrors['message'] = t('messageLength');
            if (!privacy) fieldErrors['privacy'] = t('privacyRequired');

            if (Object.keys(fieldErrors).length) {
                // show errors under each field individually
                if (fieldErrors['inquiry']) showFieldErrorFor($inquiryWrap, fieldErrors['inquiry']);
                if (fieldErrors['company']) showFieldErrorFor($companyField, fieldErrors['company']);
                if (fieldErrors['manager']) showFieldErrorFor($managerField, fieldErrors['manager']);
                if (fieldErrors['country']) showFieldErrorFor($countryField, fieldErrors['country']);
                if (fieldErrors['email']) showFieldErrorFor($emailField, fieldErrors['email']);
                if (fieldErrors['subject']) showFieldErrorFor($subjectField, fieldErrors['subject']);
                if (fieldErrors['message']) showFieldErrorFor($messageField, fieldErrors['message']);
                if (fieldErrors['privacy']) showFieldErrorFor($privacyWrap, fieldErrors['privacy']);

                // focus the first invalid field
                var order = ['inquiry','company','manager','country','email','subject','message','privacy'];
                var firstKey = order.filter(function (k) { return fieldErrors[k]; })[0];
                var $focusTarget = null;
                if (firstKey === 'inquiry') $focusTarget = $form.find('input[name="inquiry"]').first().closest('.options');
                else if (firstKey === 'privacy') $focusTarget = $privacyWrap;
                else $focusTarget = $form.find('[name="' + firstKey + '"]').closest('.field');
                if ($focusTarget && $focusTarget.length) scrollToAndFocus($focusTarget);
                return;
            }

            var payload = {
                type: inquiry.label || inquiry.value || '',
                type_value: inquiry.value || '',
                company: company,
                manager: manager,
                country: country,
                email: email,
                subject: subject,
                message: message,
                privacy: privacy ? '1' : ''
            };

            var formData = new FormData();
            Object.keys(payload).forEach(function (k) { formData.append(k, payload[k]); });
            var fileInput = $form.find('.fileinput').get(0);
            var filesToSend = [];
            if (fileControl && typeof fileControl.getFiles === 'function') {
                filesToSend = fileControl.getFiles() || [];
            }
            if (!filesToSend.length && fileInput && fileInput.files && fileInput.files.length) {
                filesToSend = Array.prototype.slice.call(fileInput.files);
            }
            if (filesToSend.length) {
                formData.append('files_expected', String(filesToSend.length));
                filesToSend.forEach(function (file) {
                    formData.append('files[]', file);
                });
            }

            setSubmitting(true);

            $.ajax({
                type: 'POST',
                url: '/api/contact.php',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json'
            }).done(function (res) {
                if (!res || !res.success) {
                    if (res && res.errors && typeof res.errors === 'object') {
                        // show each field error below its field
                        if (res.errors['inquiry']) showFieldErrorFor($inquiryWrap, res.errors['inquiry']);
                        if (res.errors['company']) showFieldErrorFor($companyField, res.errors['company']);
                        if (res.errors['manager']) showFieldErrorFor($managerField, res.errors['manager']);
                        if (res.errors['country']) showFieldErrorFor($countryField, res.errors['country']);
                        if (res.errors['email']) showFieldErrorFor($emailField, res.errors['email']);
                        if (res.errors['subject']) showFieldErrorFor($subjectField, res.errors['subject']);
                        if (res.errors['message']) showFieldErrorFor($messageField, res.errors['message']);
                        if (res.errors['privacy']) showFieldErrorFor($privacyWrap, res.errors['privacy']);
                        if (res.errors['files']) showFormError($form, res.errors['files']);
                        var order = ['inquiry','company','manager','country','email','subject','message','privacy'];
                        var firstKey = order.filter(function (k) { return res.errors[k]; })[0];
                        var $focusTarget = null;
                        if (firstKey === 'inquiry') $focusTarget = $form.find('input[name="inquiry"]').first().closest('.options');
                        else if (firstKey === 'privacy') $focusTarget = $privacyWrap;
                        else $focusTarget = $form.find('[name="' + firstKey + '"]').closest('.field');
                        if ($focusTarget && $focusTarget.length) scrollToAndFocus($focusTarget);
                    } else {
                        showFormError($form, (res && res.error) ? res.error : t('submitError'));
                        scrollToAndFocus($form);
                    }
                    setSubmitting(false);
                    return;
                }

                showFormSuccess($form, successMessage);
                $form.get(0).reset();
                if (fileControl && typeof fileControl.reset === 'function') {
                    fileControl.reset();
                }
                setSubmitting(false);
            }).fail(function (xhr) {
                if (xhr && xhr.responseJSON && xhr.responseJSON.errors) {
                    var errs = xhr.responseJSON.errors;
                    if (errs['files']) showFormError($form, errs['files']);
                    else showFormError($form, t('submitError'));
                } else {
                    showFormError($form, t('submitError'));
                }
                scrollToAndFocus($form);
                setSubmitting(false);
            });
        });
    }

    $(function () {
        var $root = $('.cont1');
        if (!$root.length) return;
        var fileControl = initFileAttach($root);
        initPolicyModal($root);
        initInquiryForm($root, fileControl);
    });
})(jQuery);
