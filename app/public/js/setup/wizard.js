// Setup Wizard JavaScript

document.addEventListener('DOMContentLoaded', function() {
    console.log('Wizard JavaScript loaded');

    let currentStep = 1;
    const totalSteps = 3;

    // Elements
    const stepItems = document.querySelectorAll('.step-item');
    const stepContents = document.querySelectorAll('.step-content');
    const roleInputs = document.querySelectorAll('.role-input');
    const roleCards = document.querySelectorAll('.role-card');

    // Navigation buttons
    const nextStep1 = document.getElementById('nextStep1');
    const nextStep2 = document.getElementById('nextStep2');
    const prevStep2 = document.getElementById('prevStep2');
    const prevStep3 = document.getElementById('prevStep3');
    const completeSetup = document.getElementById('completeSetup');

    // Debug log
    console.log('Found elements:', {
        stepItems: stepItems.length,
        stepContents: stepContents.length,
        roleInputs: roleInputs.length,
        roleCards: roleCards.length,
        nextStep1: nextStep1 ? 'found' : 'not found'
    });

    // Role selection logic
    roleCards.forEach((card, index) => {
        card.addEventListener('click', function(e) {
            console.log('Role card clicked:', index);

            // Remove selected from all cards and radios
            roleCards.forEach(c => c.classList.remove('selected'));
            roleInputs.forEach(r => r.checked = false);

            // Add selected to clicked card
            this.classList.add('selected');

            // Find and check the corresponding radio input
            const roleId = this.getAttribute('for');
            const radio = document.getElementById(roleId);

            if (radio) {
                radio.checked = true;
                console.log('Radio checked:', radio.value);

                // Enable next button
                if (nextStep1) {
                    nextStep1.disabled = false;
                    console.log('Next button enabled');
                }
            } else {
                // Fallback: try to find radio by index
                if (roleInputs[index]) {
                    roleInputs[index].checked = true;
                    console.log('Fallback: Radio checked by index:', roleInputs[index].value);

                    if (nextStep1) {
                        nextStep1.disabled = false;
                        console.log('Next button enabled (fallback)');
                    }
                }
            }
        });
    });

    // Navigation functions
    function showStep(step) {
        console.log('Showing step:', step);
        // Hide all steps
        stepContents.forEach(content => content.classList.add('d-none'));

        // Show current step
        document.getElementById(`step-${step}`).classList.remove('d-none');

        // Update progress
        updateProgress(step);
        currentStep = step;
    }

    function updateProgress(step) {
        stepItems.forEach((item, index) => {
            item.classList.remove('active', 'completed');
            if (index + 1 < step) {
                item.classList.add('completed');
            } else if (index + 1 === step) {
                item.classList.add('active');
            }
        });
    }

    // Step navigation
    if (nextStep1) {
        nextStep1.addEventListener('click', function() {
            console.log('Next step 1 clicked');

            const selectedRole = document.querySelector('input[name="role_id"]:checked');
            console.log('Selected role:', selectedRole ? selectedRole.value : 'none');

            if (selectedRole && selectedRole.value) {
                console.log('Saving role data...');
                const roleName = selectedRole.getAttribute('data-role-name');

                saveStepData('role', { role_id: selectedRole.value }, (response) => {
                    console.log('Role saved, response:', response);

                    if (response.next_step === 'invitation') {
                        // Show invitation modal for staff and business-investigator
                        showInvitationModal(roleName);
                    } else {
                        // Continue to business step for business-owner
                        showStep(2);
                    }
                });
            } else {
                alert('Silakan pilih role terlebih dahulu!');
            }
        });
    }

    if (nextStep2) {
        nextStep2.addEventListener('click', function() {
            console.log('Next step 2 clicked');

            const businessData = {
                business_name: document.querySelector('[name="business_name"]').value,
                industry: document.querySelector('[name="industry"]').value,
                description: document.querySelector('[name="description"]').value,
                founded_date: document.querySelector('[name="founded_date"]').value,
                website: document.querySelector('[name="website"]').value,
                initial_revenue: document.querySelector('[name="initial_revenue"]').value,
                initial_customers: document.querySelector('[name="initial_customers"]').value,
            };

            console.log('Business data to save:', businessData);

            // Validate required fields
            if (!businessData.business_name || !businessData.industry) {
                alert('Nama bisnis dan industri wajib diisi!');
                return;
            }

            saveStepData('business', businessData, () => {
                showStep(3);
            });
        });
    }

    if (prevStep2) {
        prevStep2.addEventListener('click', () => showStep(1));
    }

    if (prevStep3) {
        prevStep3.addEventListener('click', () => showStep(2));
    }

    if (completeSetup) {
        completeSetup.addEventListener('click', function() {
            const goalsData = {
                revenue_target: document.querySelector('[name="revenue_target"]').value,
                customer_target: document.querySelector('[name="customer_target"]').value,
                growth_rate_target: document.querySelector('[name="growth_rate_target"]').value,
                key_metrics: Array.from(document.querySelectorAll('[name="key_metrics[]"]'))
                                  .map(input => input.value)
                                  .filter(value => value.trim() !== ''),
            };

            // Validate required fields
            if (!goalsData.revenue_target || !goalsData.customer_target || !goalsData.growth_rate_target) {
                alert('Semua target wajib diisi!');
                return;
            }

            saveStepData('goals', goalsData, (response) => {
                if (response.redirect) {
                    window.location.href = response.redirect;
                }
            });
        });
    }

    // Save step data function with better error handling
    function saveStepData(step, data, callback) {
        console.log('Saving step data:', step, data);
        const formData = new FormData();
        formData.append('_token', window.csrfToken);
        formData.append('step', step);

        Object.keys(data).forEach(key => {
            if (Array.isArray(data[key])) {
                data[key].forEach(value => {
                    formData.append(`${key}[]`, value);
                });
            } else {
                formData.append(key, data[key]);
            }
        });

        fetch(window.routes.setup.store, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            console.log('Response status:', response.status);
            console.log('Response headers:', response.headers);
            return response.text().then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Failed to parse JSON response:', text);
                    throw new Error('Invalid JSON response: ' + text);
                }
            });
        })
        .then(data => {
            console.log('Data received:', data);
            if (data.success) {
                callback(data);
            } else {
                console.error('Server error:', data);
                if (data.errors) {
                    let errorMessages = [];
                    Object.values(data.errors).forEach(errorArray => {
                        errorMessages = errorMessages.concat(errorArray);
                    });
                    alert('Error: ' + errorMessages.join(', '));
                } else {
                    alert(data.message || 'Terjadi kesalahan. Silakan coba lagi.');
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Terjadi kesalahan: ' + error.message);
        });
    }

    // Invitation modal functions
    function showInvitationModal(roleName) {
        console.log('showInvitationModal called with role:', roleName);
        const modal = document.getElementById('invitationModal');
        const invitationCodeField = document.getElementById('invitationCodeField');
        const publicIdInput = document.getElementById('publicId');
        const invitationCodeInput = document.getElementById('invitationCode');

        if (!modal || !publicIdInput) {
            console.error('Modal elements not found');
            return;
        }

        // Clear previous values and ensure inputs are fully enabled
        publicIdInput.value = '';
        if (invitationCodeInput) {
            invitationCodeInput.value = '';
        }

        // Show invitation code field for staff only
        if (roleName === 'staff') {
            invitationCodeField.style.display = 'block';
        } else {
            invitationCodeField.style.display = 'none';
        }

        // Show modal using Bootstrap if available
        if (typeof window.bootstrap !== 'undefined' && window.bootstrap.Modal) {
            console.log('Using Bootstrap modal');
            const instance = window.bootstrap.Modal.getOrCreateInstance(modal, {
                backdrop: 'static',
                keyboard: false
            });
            instance.show();
        } else {
            console.log('Using fallback modal');
            // Simplified fallback without complex focus management
            modal.style.display = 'block';
            modal.classList.add('show');
            document.body.classList.add('modal-open');
        }
    }

    // Setup input event listeners - simplified approach
    ['publicId', 'invitationCode'].forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            console.log('Setting up event listeners for:', id);

            // Ensure input is enabled
            el.disabled = false;
            el.readOnly = false;
            
            // Basic event listeners without interference
            el.addEventListener('keydown', (e) => {
                console.log('Keydown on', id, ':', e.key);
                if (e.key === 'Enter') {
                    e.preventDefault();
                    document.getElementById('submitInvitation')?.click();
                }
            });

            el.addEventListener('input', (e) => {
                console.log('Input event on', id, ':', e.target.value);
            });

            el.addEventListener('click', (e) => {
                console.log('Click event on', id);
                e.target.focus();
            });
        }
    });

    // Handle invitation form submission
    const submitInvitationBtn = document.getElementById('submitInvitation');
    if (submitInvitationBtn) {
        submitInvitationBtn.addEventListener('click', function() {
            const publicId = document.getElementById('publicId').value;
            const invitationCode = document.getElementById('invitationCode').value;
            const selectedRole = document.querySelector('input[name="role_id"]:checked');

            if (!publicId) {
                alert('ID Dashboard Perusahaan wajib diisi!');
                return;
            }

            const roleName = selectedRole ? selectedRole.getAttribute('data-role-name') : '';

            // Only staff needs invitation code, business-investigator just needs public_id
            if (roleName === 'staff' && !invitationCode) {
                alert('Kode Undangan Staff wajib diisi!');
                return;
            }

            const invitationData = {
                public_id: publicId,
            };

            // Only add invitation code for staff
            if (roleName === 'staff') {
                invitationData.invitation_code = invitationCode;
            }

            console.log('Submitting invitation data:', invitationData);

            saveStepData('invitation', invitationData, (response) => {
                if (response.redirect) {
                    window.location.href = response.redirect;
                }
            });
        });
    }
});