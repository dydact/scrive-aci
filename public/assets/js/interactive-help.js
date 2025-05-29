/**
 * Interactive Help System for Scrive ACI
 * Provides guided tours, contextual help, and keyboard shortcuts
 */

(function() {
    'use strict';
    
    // Main InteractiveHelp object
    window.InteractiveHelp = {
        // Configuration
        tours: {
            'first-login': {
                name: 'First Login Tour',
                steps: [
                    {
                        target: '#username',
                        title: 'Enter Your Username',
                        content: 'Type your username here. This is usually your email address or employee ID.',
                        position: 'right'
                    },
                    {
                        target: '#password',
                        title: 'Enter Your Password',
                        content: 'Type your temporary password here. You\'ll be asked to change it after logging in.',
                        position: 'right'
                    },
                    {
                        target: '#login-button',
                        title: 'Click to Login',
                        content: 'Click this button to access your dashboard.',
                        position: 'top'
                    }
                ]
            },
            'dashboard-overview': {
                name: 'Dashboard Overview',
                steps: [
                    {
                        target: '.role-banner',
                        title: 'Your Role',
                        content: 'This shows your current role and access level in the system.',
                        position: 'bottom'
                    },
                    {
                        target: '.quick-actions',
                        title: 'Quick Actions',
                        content: 'These buttons give you one-click access to your most common tasks.',
                        position: 'bottom'
                    },
                    {
                        target: '.stats-grid',
                        title: 'Your Statistics',
                        content: 'Real-time data about your clients, sessions, and performance.',
                        position: 'top'
                    },
                    {
                        target: '.logout-btn',
                        title: 'Logout Safely',
                        content: 'Always logout when you\'re done to keep client data secure.',
                        position: 'left'
                    }
                ]
            },
            'create-session': {
                name: 'Creating Session Notes',
                steps: [
                    {
                        target: '.client-select',
                        title: 'Select Your Client',
                        content: 'Choose the client you worked with from this dropdown.',
                        position: 'bottom'
                    },
                    {
                        target: '.service-type',
                        title: 'Service Type',
                        content: 'Select the type of service provided (IISS, Respite, etc.)',
                        position: 'bottom'
                    },
                    {
                        target: '.session-duration',
                        title: 'Session Duration',
                        content: 'Enter how long the session lasted in minutes.',
                        position: 'bottom'
                    },
                    {
                        target: '.session-notes',
                        title: 'Document Activities',
                        content: 'Describe what you did during the session. Be specific and objective.',
                        position: 'top'
                    },
                    {
                        target: '.goal-ratings',
                        title: 'Rate Progress',
                        content: 'Rate the client\'s progress on each goal using the 1-5 scale.',
                        position: 'top'
                    }
                ]
            },
            'mobile-portal': {
                name: 'Mobile Portal Guide',
                steps: [
                    {
                        target: '.mobile-portal-btn',
                        title: 'Access Mobile Portal',
                        content: 'Click here to open the mobile-friendly version of the system.',
                        position: 'bottom'
                    },
                    {
                        target: '.client-list',
                        title: 'Your Clients',
                        content: 'All your assigned clients appear here. Tap to view details.',
                        position: 'bottom'
                    },
                    {
                        target: '.quick-note-btn',
                        title: 'Quick Documentation',
                        content: 'Start documenting a session with one tap.',
                        position: 'left'
                    }
                ]
            },
            'treatment-plan': {
                name: 'Treatment Plan Management',
                steps: [
                    {
                        target: '.plan-overview',
                        title: 'Plan Overview',
                        content: 'See all active treatment plans and their status.',
                        position: 'bottom'
                    },
                    {
                        target: '.add-goal-btn',
                        title: 'Add New Goals',
                        content: 'Click here to add new treatment goals for your client.',
                        position: 'left'
                    },
                    {
                        target: '.progress-chart',
                        title: 'Track Progress',
                        content: 'Visual representation of goal progress over time.',
                        position: 'top'
                    }
                ]
            },
            'mobile-session': {
                name: 'Mobile Session Entry',
                steps: [
                    {
                        target: '.session-form',
                        title: 'Session Form',
                        content: 'Fill out session details on your mobile device.',
                        position: 'top'
                    },
                    {
                        target: '.save-draft-btn',
                        title: 'Save as Draft',
                        content: 'Save your work and complete it later if needed.',
                        position: 'top'
                    },
                    {
                        target: '.submit-btn',
                        title: 'Submit Session',
                        content: 'Submit when complete. Works offline too!',
                        position: 'top'
                    }
                ]
            }
        },
        
        // Current tour state
        currentTour: null,
        currentStep: 0,
        
        // Initialize the help system
        init: function() {
            this.setupKeyboardShortcuts();
            this.checkFirstTimeUser();
            this.addHelpButtons();
        },
        
        // Start a specific tour
        startTour: function(tourName) {
            const tour = this.tours[tourName];
            if (!tour) {
                console.error('Tour not found:', tourName);
                return;
            }
            
            this.currentTour = tourName;
            this.currentStep = 0;
            this.showOverlay();
            this.showStep();
        },
        
        // Show tour overlay
        showOverlay: function() {
            // Remove existing overlay if any
            this.hideOverlay();
            
            // Create overlay
            const overlay = document.createElement('div');
            overlay.id = 'help-overlay';
            overlay.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0,0,0,0.7);
                z-index: 9998;
            `;
            
            // Create tooltip container
            const tooltip = document.createElement('div');
            tooltip.id = 'help-tooltip';
            tooltip.style.cssText = `
                position: absolute;
                background: white;
                padding: 20px;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.3);
                max-width: 400px;
                z-index: 9999;
            `;
            
            document.body.appendChild(overlay);
            document.body.appendChild(tooltip);
            
            // Click overlay to exit
            overlay.addEventListener('click', () => this.endTour());
        },
        
        // Hide tour overlay
        hideOverlay: function() {
            const overlay = document.getElementById('help-overlay');
            const tooltip = document.getElementById('help-tooltip');
            if (overlay) overlay.remove();
            if (tooltip) tooltip.remove();
        },
        
        // Show current step
        showStep: function() {
            const tour = this.tours[this.currentTour];
            const step = tour.steps[this.currentStep];
            const tooltip = document.getElementById('help-tooltip');
            
            if (!tooltip || !step) return;
            
            // Update tooltip content
            tooltip.innerHTML = `
                <h4 style="margin-top: 0; color: #1e40af;">${step.title}</h4>
                <p style="color: #64748b; margin: 10px 0;">${step.content}</p>
                <div style="display: flex; justify-content: space-between; margin-top: 20px;">
                    <button onclick="InteractiveHelp.prevStep()" 
                            style="padding: 8px 16px; border: 1px solid #e5e7eb; background: white; border-radius: 6px; cursor: pointer;"
                            ${this.currentStep === 0 ? 'disabled' : ''}>
                        Previous
                    </button>
                    <span style="color: #94a3b8;">
                        ${this.currentStep + 1} / ${tour.steps.length}
                    </span>
                    <button onclick="InteractiveHelp.nextStep()" 
                            style="padding: 8px 16px; background: #1e40af; color: white; border: none; border-radius: 6px; cursor: pointer;">
                        ${this.currentStep === tour.steps.length - 1 ? 'Finish' : 'Next'}
                    </button>
                </div>
                <button onclick="InteractiveHelp.endTour()" 
                        style="position: absolute; top: 10px; right: 10px; background: none; border: none; font-size: 20px; cursor: pointer;">
                    ×
                </button>
            `;
            
            // Position tooltip near target element
            const target = document.querySelector(step.target);
            if (target) {
                const rect = target.getBoundingClientRect();
                const tooltipRect = tooltip.getBoundingClientRect();
                
                // Highlight target
                target.style.position = 'relative';
                target.style.zIndex = '10000';
                target.style.boxShadow = '0 0 0 4px rgba(30, 64, 175, 0.5)';
                
                // Position tooltip
                switch(step.position) {
                    case 'right':
                        tooltip.style.left = (rect.right + 20) + 'px';
                        tooltip.style.top = rect.top + 'px';
                        break;
                    case 'left':
                        tooltip.style.left = (rect.left - tooltipRect.width - 20) + 'px';
                        tooltip.style.top = rect.top + 'px';
                        break;
                    case 'bottom':
                        tooltip.style.left = rect.left + 'px';
                        tooltip.style.top = (rect.bottom + 20) + 'px';
                        break;
                    case 'top':
                        tooltip.style.left = rect.left + 'px';
                        tooltip.style.top = (rect.top - tooltipRect.height - 20) + 'px';
                        break;
                }
            } else {
                // Center tooltip if target not found
                tooltip.style.left = '50%';
                tooltip.style.top = '50%';
                tooltip.style.transform = 'translate(-50%, -50%)';
            }
        },
        
        // Next step
        nextStep: function() {
            const tour = this.tours[this.currentTour];
            
            // Remove highlight from current target
            const currentStep = tour.steps[this.currentStep];
            const currentTarget = document.querySelector(currentStep.target);
            if (currentTarget) {
                currentTarget.style.boxShadow = '';
                currentTarget.style.zIndex = '';
            }
            
            if (this.currentStep < tour.steps.length - 1) {
                this.currentStep++;
                this.showStep();
            } else {
                this.endTour();
            }
        },
        
        // Previous step
        prevStep: function() {
            if (this.currentStep > 0) {
                // Remove highlight from current target
                const tour = this.tours[this.currentTour];
                const currentStep = tour.steps[this.currentStep];
                const currentTarget = document.querySelector(currentStep.target);
                if (currentTarget) {
                    currentTarget.style.boxShadow = '';
                    currentTarget.style.zIndex = '';
                }
                
                this.currentStep--;
                this.showStep();
            }
        },
        
        // End tour
        endTour: function() {
            // Remove highlights
            document.querySelectorAll('[style*="box-shadow"]').forEach(el => {
                if (el.style.boxShadow.includes('rgba(30, 64, 175')) {
                    el.style.boxShadow = '';
                    el.style.zIndex = '';
                }
            });
            
            this.hideOverlay();
            this.currentTour = null;
            this.currentStep = 0;
            
            // Mark tour as completed
            if (this.currentTour) {
                localStorage.setItem('tour-completed-' + this.currentTour, 'true');
            }
        },
        
        // Setup keyboard shortcuts
        setupKeyboardShortcuts: function() {
            document.addEventListener('keydown', (e) => {
                // F1 - Show help
                if (e.key === 'F1') {
                    e.preventDefault();
                    this.showHelpModal();
                }
                
                // Escape - Close tour
                if (e.key === 'Escape' && this.currentTour) {
                    this.endTour();
                }
                
                // Arrow keys during tour
                if (this.currentTour) {
                    if (e.key === 'ArrowRight') {
                        e.preventDefault();
                        this.nextStep();
                    } else if (e.key === 'ArrowLeft') {
                        e.preventDefault();
                        this.prevStep();
                    }
                }
            });
        },
        
        // Check if first time user
        checkFirstTimeUser: function() {
            // Check if user has seen any tour
            const hasSeenTour = localStorage.getItem('has-seen-tour');
            
            if (!hasSeenTour) {
                // Delay to let page load
                setTimeout(() => {
                    if (confirm('Welcome! Would you like a quick tour of the system?')) {
                        // Determine which tour to show based on current page
                        if (window.location.pathname.includes('login')) {
                            this.startTour('first-login');
                        } else if (window.location.pathname.includes('dashboard')) {
                            this.startTour('dashboard-overview');
                        }
                    }
                    localStorage.setItem('has-seen-tour', 'true');
                }, 1000);
            }
        },
        
        // Add help buttons to forms
        addHelpButtons: function() {
            // Add ? icons to form labels
            document.querySelectorAll('label').forEach(label => {
                if (label.dataset.help) {
                    const helpIcon = document.createElement('span');
                    helpIcon.innerHTML = ' ?';
                    helpIcon.style.cssText = `
                        display: inline-block;
                        width: 16px;
                        height: 16px;
                        border-radius: 50%;
                        background: #e5e7eb;
                        color: #64748b;
                        font-size: 12px;
                        text-align: center;
                        line-height: 16px;
                        cursor: pointer;
                        margin-left: 4px;
                    `;
                    helpIcon.title = label.dataset.help;
                    label.appendChild(helpIcon);
                }
            });
        },
        
        // Show help modal
        showHelpModal: function() {
            const modal = document.createElement('div');
            modal.style.cssText = `
                position: fixed;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                background: white;
                padding: 30px;
                border-radius: 12px;
                box-shadow: 0 10px 25px rgba(0,0,0,0.2);
                max-width: 500px;
                z-index: 10000;
            `;
            
            modal.innerHTML = `
                <h3 style="margin-top: 0; color: #1e293b;">Keyboard Shortcuts</h3>
                <table style="width: 100%; margin-top: 20px;">
                    <tr>
                        <td style="padding: 8px;"><kbd style="background: #e5e7eb; padding: 4px 8px; border-radius: 4px;">F1</kbd></td>
                        <td style="padding: 8px;">Show this help</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px;"><kbd style="background: #e5e7eb; padding: 4px 8px; border-radius: 4px;">Ctrl+S</kbd></td>
                        <td style="padding: 8px;">Save current form</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px;"><kbd style="background: #e5e7eb; padding: 4px 8px; border-radius: 4px;">Ctrl+/</kbd></td>
                        <td style="padding: 8px;">Search</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px;"><kbd style="background: #e5e7eb; padding: 4px 8px; border-radius: 4px;">Esc</kbd></td>
                        <td style="padding: 8px;">Close dialogs</td>
                    </tr>
                </table>
                <button onclick="this.parentElement.remove()" 
                        style="margin-top: 20px; padding: 10px 20px; background: #1e40af; color: white; border: none; border-radius: 6px; cursor: pointer;">
                    Close
                </button>
            `;
            
            document.body.appendChild(modal);
            
            // Close on escape
            const closeOnEscape = (e) => {
                if (e.key === 'Escape') {
                    modal.remove();
                    document.removeEventListener('keydown', closeOnEscape);
                }
            };
            document.addEventListener('keydown', closeOnEscape);
        }
    };
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => InteractiveHelp.init());
    } else {
        InteractiveHelp.init();
    }
})();