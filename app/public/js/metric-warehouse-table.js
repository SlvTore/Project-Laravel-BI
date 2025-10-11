(function(){
    const section = document.getElementById('warehouse-data-section');
    if(!section) return;

    const metricId = section.getAttribute('data-metric-id');
    const tableHeadRow = document.getElementById('dwHeadRow');
    const tableBody = document.getElementById('dwBody');
    const loading = document.getElementById('dwLoading');
    const metaEl = document.getElementById('dwMeta');
    const footer = document.getElementById('dwFooter');
    const prevBtn = document.getElementById('dwPrev');
    const nextBtn = document.getElementById('dwNext');
    const perPageSel = document.getElementById('dwPerPage');
    const refreshBtn = document.getElementById('dwRefreshBtn');

    let state = {
        sort: 'sales_date',
        dir: 'desc',
        page: 1,
        per_page: parseInt(perPageSel.value,10) || 25,
        total: 0,
        columns: [],
        metric: ''
    };

    function showLoading(v){ loading.style.display = v? 'flex':'none'; }

    function fetchData(){
        showLoading(true);
        const params = new URLSearchParams({
            sort: state.sort,
            dir: state.dir,
            page: state.page,
            per_page: state.per_page
        });
        fetch(`/dashboard/metrics/${metricId}/warehouse-data?`+params.toString(), {
            headers: { 'Accept': 'application/json' }
        }).then(r=>r.json()).then(json => {
            if(!json.success){
                tableBody.innerHTML = `<tr><td class="text-danger">${json.message||'Gagal memuat data'}</td></tr>`;
                footer.style.display='none';
                return;
            }
            state.total = json.total;
            state.columns = json.columns;
            state.metric = json.metric;
            renderTable(json.columns, json.rows);
            renderFooter(json.page, json.per_page, json.total);
        }).catch(err => {
            tableBody.innerHTML = `<tr><td class="text-danger">${err.message}</td></tr>`;
            footer.style.display='none';
        }).finally(()=> showLoading(false));
    }

    function renderTable(columns, rows){
        // Head
        tableHeadRow.innerHTML = '';
        columns.forEach(col => {
            const th = document.createElement('th');
            th.className = 'sortable position-relative';
            th.dataset.col = col;
            th.style.cursor = 'pointer';
            th.innerHTML = `<span>${humanize(col)}</span>` + (state.sort===col?` <i class="fas fa-caret-${state.dir==='asc'?'up':'down'}"></i>`:'');
            th.addEventListener('click', ()=>{
                if(state.sort === col){
                    state.dir = state.dir === 'asc'? 'desc':'asc';
                } else {
                    state.sort = col; state.dir = 'desc';
                }
                state.page = 1;
                fetchData();
            });
            tableHeadRow.appendChild(th);
        });

        // Body
        if(!rows.length){
            tableBody.innerHTML = '<tr><td colspan="'+columns.length+'" class="text-muted">Tidak ada data</td></tr>';
            return;
        }
        const frag = document.createDocumentFragment();
        rows.forEach(r => {
            const tr = document.createElement('tr');
            columns.forEach(c => {
                const td = document.createElement('td');
                td.textContent = formatCell(c, r[c]);
                tr.appendChild(td);
            });
            frag.appendChild(tr);
        });
        tableBody.innerHTML='';
        tableBody.appendChild(frag);
    }

    function formatCell(col, val){
        if(val==null) return '-';
        if(col.includes('date')) return formatDate(val);
        if(/revenue|amount|margin|cogs|subtotal|tax|shipping|discount/i.test(col)) return formatNumber(val, true);
        if(/quantity|qty|orders|customers/i.test(col)) return formatNumber(val, false);
        return val;
    }

    function formatDate(d){
        try { return new Date(d).toLocaleDateString('id-ID',{day:'2-digit',month:'short'}); } catch { return d; }
    }

    function formatNumber(n, money){
        const num = Number(n)||0;
        if(money){
            return 'Rp ' + num.toLocaleString('id-ID');
        }
        return num.toLocaleString('id-ID');
    }

    function humanize(s){
        return s.replace(/_/g,' ')      
                .replace(/\b\w/g, c=>c.toUpperCase());
    }

    function renderFooter(page, per, total){
        if(total===0){ footer.style.display='none'; return; }
        footer.style.display='flex';
        const totalPages = Math.ceil(total / per);
        prevBtn.disabled = page <= 1;
        nextBtn.disabled = page >= totalPages;
        metaEl.textContent = `Halaman ${page} dari ${totalPages} • Total ${total.toLocaleString('id-ID')} baris`;
    }

    prevBtn.addEventListener('click', ()=>{ if(state.page>1){ state.page--; fetchData(); }});
    nextBtn.addEventListener('click', ()=>{ const totalPages = Math.ceil(state.total/state.per_page); if(state.page<totalPages){ state.page++; fetchData(); }});
    perPageSel.addEventListener('change', ()=>{ state.per_page = parseInt(perPageSel.value,10)||25; state.page=1; fetchData(); });
    refreshBtn.addEventListener('click', ()=>{ fetchData(); });

    // Lazy load when visible (intersection)
    const observer = new IntersectionObserver(entries => {
        entries.forEach(e => {
            if(e.isIntersecting){ fetchData(); observer.disconnect(); }
        });
    }, {threshold: 0.1});
    observer.observe(section);
})();
