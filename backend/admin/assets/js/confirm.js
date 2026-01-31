// Central confirm modal for delete actions
document.addEventListener('DOMContentLoaded', function() {
    // Inject styles
    var style = document.createElement('style');
    style.innerHTML = `
    .confirm-overlay{position:fixed;inset:0;background:rgba(0,0,0,0.45);display:flex;align-items:center;justify-content:center;z-index:10000}
    .confirm-modal{background:#fff;padding:18px;border-radius:8px;max-width:480px;width:90%;box-shadow:0 8px 24px rgba(0,0,0,0.2);}
    .confirm-modal h3{margin:0 0 8px;font-size:18px}
    .confirm-modal p{margin:0 0 14px;color:#444}
    .confirm-actions{display:flex;gap:8px;justify-content:flex-end}
    .confirm-actions .btn{padding:8px 12px;border-radius:6px;border:none;cursor:pointer}
    .confirm-actions .btn-cancel{background:#95a5a6;color:#fff}
    .confirm-actions .btn-confirm{background:#e74c3c;color:#fff}
    `;
    document.head.appendChild(style);

    // Add modal DOM
    var modalHTML = document.createElement('div');
    modalHTML.id = 'confirmOverlay';
    modalHTML.className = 'confirm-overlay';
    modalHTML.style.display = 'none';
    modalHTML.innerHTML = `
        <div class="confirm-modal" role="dialog" aria-modal="true" aria-labelledby="confirmTitle">
            <h3 id="confirmTitle">Confirm action</h3>
            <p id="confirmMessage">Are you sure you want to continue?</p>
            <div class="confirm-actions">
                <button class="btn btn-cancel" id="confirmCancel">Cancel</button>
                <button class="btn btn-confirm" id="confirmOk">Confirm</button>
            </div>
        </div>`;
    document.body.appendChild(modalHTML);

    var overlay = document.getElementById('confirmOverlay');
    var msgEl = document.getElementById('confirmMessage');
    var okBtn = document.getElementById('confirmOk');
    var cancelBtn = document.getElementById('confirmCancel');
    var pendingHref = null;

    // Delegated handler for links with .confirm-delete
    document.body.addEventListener('click', function(e){
        var el = e.target.closest('a.confirm-delete');
        if (!el) return;
        e.preventDefault();
        pendingHref = el.getAttribute('href');
        var message = el.dataset.msg || el.getAttribute('title') || 'Are you sure you want to delete this item?';
        msgEl.textContent = message;
        overlay.style.display = 'flex';
        // set focus to cancel for accessibility
        cancelBtn.focus();
    });

    cancelBtn.addEventListener('click', function(){ overlay.style.display = 'none'; pendingHref = null; });
    okBtn.addEventListener('click', function(){ if (pendingHref) { window.location.href = pendingHref; } overlay.style.display = 'none'; pendingHref = null; });

    // close on ESC
    document.addEventListener('keydown', function(evt){ if(evt.key === 'Escape'){ overlay.style.display = 'none'; pendingHref = null; } });
});