// Dashboard Main Page JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Load goals
    fetch(window.routes.goals.index)
        .then(r => r.json())
        .then(renderGoals)
        .catch(()=>{});

    // Refresh AI insight
    const aiBtn = document.getElementById('refreshInsight');
    if (aiBtn) aiBtn.addEventListener('click', function(){
            const box = document.getElementById('aiInsight');
            if (box) box.innerHTML = '<div class="text-muted"><span class="spinner-border spinner-border-sm me-2"></span>Meminta insight...</div>';
            fetch(window.routes.ai.insight, {method: 'POST', headers: {'X-CSRF-TOKEN': window.csrfToken }})
                .then(r => r.json())
                .then(d => { box.innerHTML = d.success ? d.response.replaceAll('\n','<br>') : '<span class="text-danger">'+(d.error||'AI error')+'</span>'; })
                .catch(()=>{ box.innerHTML = '<span class="text-danger">Gagal memuat insight</span>'; });
    });
});

function renderGoals(goals){
    const list = document.getElementById('goalList');
    if (!list) return;
    list.innerHTML = '';
    goals.forEach(g => {
        const li = document.createElement('li');
        li.className = 'list-group-item d-flex align-items-start justify-content-between gap-3';
        li.innerHTML = `
            <div class="form-check">
                <input class="form-check-input" type="checkbox" ${g.is_done ? 'checked' : ''} onchange="toggleGoal(${g.id})">
                <label class="form-check-label text-white">${escapeHtml(g.title)}</label>
                <div class="small text-muted">Target ${g.target_percent}%</div>
                <div class="progress mt-1">
                    <div class="progress-bar ${g.is_done ? 'bg-success' : 'bg-primary'}" style="width:${g.current_percent}%"></div>
                </div>
            </div>
            <div class="btn-group btn-group-sm">
                <button class="btn btn-outline-light" onclick="editGoal(${g.id})"><i class="bi bi-pencil"></i></button>
                <button class="btn btn-outline-danger" onclick="deleteGoal(${g.id})"><i class="bi bi-trash"></i></button>
            </div>`;
        list.appendChild(li);
    });
}

function toggleGoal(id){
    fetch(`${window.routes.goals.base}/${id}/toggle`, {method:'POST', headers:{'X-CSRF-TOKEN': window.csrfToken}})
        .then(()=>fetch(window.routes.goals.index).then(r=>r.json()).then(renderGoals));
}

function deleteGoal(id){
    if (!confirm('Delete this goal?')) return;
    fetch(`${window.routes.goals.base}/${id}`, {method:'DELETE', headers:{'X-CSRF-TOKEN': window.csrfToken}})
        .then(()=>fetch(window.routes.goals.index).then(r=>r.json()).then(renderGoals));
}

function escapeHtml(s){
    return (s||'').replace(/[&<>"]+/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));
}

function submitGoal(){
    const form = document.getElementById('goalForm');
    const fd = new FormData(form);
    fetch(window.routes.goals.store, {method:'POST', headers:{'X-CSRF-TOKEN': window.csrfToken}, body: fd})
        .then(r=>r.json())
        .then(()=>{ form.reset(); bootstrap.Modal.getInstance(document.getElementById('goalModal')).hide(); return fetch(window.routes.goals.index) })
        .then(r=>r.json()).then(renderGoals)
        .catch(()=>{});
}

function editGoal(id){ 
    alert('Inline edit can be implemented similarly (update endpoint available).'); 
}