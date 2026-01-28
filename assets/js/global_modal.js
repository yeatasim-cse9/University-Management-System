/**
 * Global Modal Utility
 * Replaces native alerts with a brutalist styled modal.
 */

document.addEventListener("DOMContentLoaded", function () {
    // Inject Modal HTML
    const modalHTML = `
    <div id="global-modal" class="relative z-[10000] hidden" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-black/80 backdrop-blur-sm transition-opacity opacity-0" id="global-modal-backdrop"></div>
        <div class="fixed inset-0 z-[10001] w-screen overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4 text-center">
                <div class="relative transform overflow-hidden bg-white text-left shadow-[8px_8px_0px_#000] border-4 border-black transition-all scale-95 opacity-0 w-full max-w-sm" id="global-modal-panel">
                    <div class="bg-black p-4 flex justify-between items-center border-b-4 border-black">
                        <h3 class="text-lg font-black text-yellow-400 uppercase tracking-tighter" id="global-modal-title">Notification</h3>
                        <button onclick="closeGlobalModal()" class="text-white hover:text-yellow-400 transition-colors">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>
                    <div class="p-6">
                        <div class="flex items-start gap-4">
                            <div id="global-modal-icon" class="text-2xl pt-1"></div>
                            <div>
                                <p class="text-sm font-bold text-black uppercase leading-relaxed" id="global-modal-message"></p>
                            </div>
                        </div>
                        <div class="mt-6 flex justify-end gap-3" id="global-modal-actions">
                            <button onclick="closeGlobalModal()" class="w-full py-3 bg-black text-white text-xs font-black uppercase tracking-widest hover:bg-yellow-400 hover:text-black border-2 border-black transition-all shadow-os">
                                Acknowledge
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Confirm Modal Structure -->
    <div id="confirm-modal" class="relative z-[10000] hidden" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-black/90 backdrop-blur-md transition-opacity opacity-0" id="confirm-modal-backdrop"></div>
        <div class="fixed inset-0 z-[10001] w-screen overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4 text-center">
                <div class="relative transform overflow-hidden bg-yellow-400 text-left shadow-[12px_12px_0px_#fff] border-4 border-white transition-all scale-95 opacity-0 w-full max-w-md" id="confirm-modal-panel">
                    <div class="p-8">
                        <h3 class="text-2xl font-black text-black uppercase tracking-tighter mb-4">CONFIRM ACTION</h3>
                        <p class="text-base font-bold text-black uppercase leading-relaxed mb-8" id="confirm-modal-message">Are you sure?</p>
                        <div class="flex gap-4">
                            <button id="confirm-btn-cancel" class="flex-1 py-4 bg-white text-black text-sm font-black uppercase tracking-widest hover:bg-black hover:text-white border-4 border-black transition-all">
                                Cancel
                            </button>
                            <button id="confirm-btn-yes" class="flex-1 py-4 bg-black text-white text-sm font-black uppercase tracking-widest hover:bg-white hover:text-black border-4 border-black transition-all">
                                PROCEED
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    `;
    document.body.insertAdjacentHTML('beforeend', modalHTML);
});

window.showGlobalModal = function (title, message, type = 'info') {
    const modal = document.getElementById('global-modal');
    const backdrop = document.getElementById('global-modal-backdrop');
    const panel = document.getElementById('global-modal-panel');
    const titleEl = document.getElementById('global-modal-title');
    const messageEl = document.getElementById('global-modal-message');
    const iconEl = document.getElementById('global-modal-icon');

    // Content
    titleEl.innerText = title;
    messageEl.innerText = message;

    // Type Styling
    let icon = '';
    let titleColor = 'text-yellow-400';

    switch (type) {
        case 'success':
            icon = '<i class="fas fa-check-circle text-green-600"></i>';
            titleColor = 'text-green-500';
            break;
        case 'error':
            icon = '<i class="fas fa-exclamation-circle text-red-600"></i>';
            titleColor = 'text-red-500';
            break;
        case 'warning':
            icon = '<i class="fas fa-exclamation-triangle text-yellow-600"></i>';
            titleColor = 'text-yellow-400';
            break;
        default:
            icon = '<i class="fas fa-info-circle text-blue-600"></i>';
            titleColor = 'text-yellow-400';
    }

    iconEl.innerHTML = icon;
    titleEl.className = `text-lg font-black uppercase tracking-tighter ${titleColor}`;

    // Show
    modal.classList.remove('hidden');
    // Animate in
    setTimeout(() => {
        backdrop.classList.remove('opacity-0');
        panel.classList.remove('scale-95', 'opacity-0');
        panel.classList.add('scale-100', 'opacity-100');
    }, 10);
};

window.closeGlobalModal = function () {
    const modal = document.getElementById('global-modal');
    const backdrop = document.getElementById('global-modal-backdrop');
    const panel = document.getElementById('global-modal-panel');

    // Animate out
    backdrop.classList.add('opacity-0');
    panel.classList.remove('scale-100', 'opacity-100');
    panel.classList.add('scale-95', 'opacity-0');

    setTimeout(() => {
        modal.classList.add('hidden');
    }, 300);
};

// Global Confirm Logic
let confirmCallback = null;

window.showConfirmModal = function (message, onConfirm) {
    const modal = document.getElementById('confirm-modal');
    const backdrop = document.getElementById('confirm-modal-backdrop');
    const panel = document.getElementById('confirm-modal-panel');
    const msgEl = document.getElementById('confirm-modal-message');

    msgEl.innerText = message;
    confirmCallback = onConfirm;

    modal.classList.remove('hidden');
    setTimeout(() => {
        backdrop.classList.remove('opacity-0');
        panel.classList.remove('scale-95', 'opacity-0');
        panel.classList.add('scale-100', 'opacity-100');
    }, 10);
};

window.closeConfirmModal = function () {
    const modal = document.getElementById('confirm-modal');
    const backdrop = document.getElementById('confirm-modal-backdrop');
    const panel = document.getElementById('confirm-modal-panel');

    backdrop.classList.add('opacity-0');
    panel.classList.remove('scale-100', 'opacity-100');
    panel.classList.add('scale-95', 'opacity-0');

    setTimeout(() => {
        modal.classList.add('hidden');
    }, 300);
};

// Init Confirm Listeners (Post DOM Ready)
document.addEventListener("DOMContentLoaded", function () {
    const cancelBtn = document.getElementById('confirm-btn-cancel');
    const yesBtn = document.getElementById('confirm-btn-yes');

    if (cancelBtn) cancelBtn.addEventListener('click', closeConfirmModal);
    if (yesBtn) yesBtn.addEventListener('click', function () {
        if (confirmCallback) confirmCallback();
        closeConfirmModal();
    });
});
