// ============================================================
//  L E I T O R   S O C I A L  -  VERSÃO COMPLETA
//  Login via clique no botão + credentials: 'include' para manter sessão
// ============================================================

(function() {
    'use strict';

    const DEBUG = true;
    function log(...args) { if (DEBUG) console.log('[📚]', ...args); }
    function logError(...args) { console.error('[❌]', ...args); }

    log('Inicializando aplicação (modo botão)...');

    // ============================================================
    // 1. ELEMENTOS DOM
    // ============================================================
    function getEl(id) {
        const el = document.getElementById(id);
        if (!el) logError(`Elemento "#${id}" não encontrado.`);
        return el;
    }

    const E = {
        searchInput: getEl('search-input'),
        searchBtn: getEl('search-btn'),
        searchResults: getEl('search-results'),
        libraryDiv: getEl('my-library'),
        libraryEmpty: getEl('library-empty'),
        authButtons: getEl('auth-buttons'),
        userInfo: getEl('user-info'),
        userNameDisplay: getEl('user-name-display'),
        authModal: getEl('authModal'),
        authForm: getEl('auth-form'),
        authSubmitBtn: getEl('auth-submit-btn'),
        authError: getEl('auth-error'),
        registerFields: getEl('register-fields'),
        reviewModal: getEl('reviewModal'),
        reviewUserBookId: getEl('review-user-book-id'),
        reviewStatus: getEl('review-status'),
        reviewRating: getEl('review-rating'),
        reviewText: getEl('review-text'),
        reviewStartedAt: getEl('review-started-at'),
        reviewFinishedAt: getEl('review-finished-at'),
        reviewTags: getEl('review-tags'),
        saveReviewBtn: getEl('save-review-btn'),
        toast: getEl('liveToast'),
        toastMessage: getEl('toast-message'),
        bookCount: getEl('book-count'),
        countWantToRead: getEl('count-want_to_read'),
        countReading: getEl('count-reading'),
        countFinished: getEl('count-finished'),
        btnLogin: getEl('btn-login'),
        btnRegister: getEl('btn-register'),
        btnLogout: getEl('btn-logout'),
        btnProfile: getEl('btn-profile'),
        authEmail: getEl('auth-email'),
        authPassword: getEl('auth-password'),
        regName: getEl('reg-name'),
        filterTags: getEl('filter-tags'),
        searchLibrary: getEl('search-library'),
        clearSearchBtn: getEl('clear-search-btn'),
        filteredCount: getEl('filtered-count'),
    };

    // ============================================================
    // 2. COMPONENTES BOOTSTRAP
    // ============================================================
    let authModalInstance = null;
    let reviewModalInstance = null;
    let toastInstance = null;

    try {
        if (E.authModal) authModalInstance = new bootstrap.Modal(E.authModal);
        if (E.reviewModal) reviewModalInstance = new bootstrap.Modal(E.reviewModal);
        if (E.toast) toastInstance = new bootstrap.Toast(E.toast, { delay: 3000 });
    } catch (e) {
        logError('Bootstrap não carregou:', e);
    }

    function showToast(msg, type = 'success') {
        log('Toast:', msg, type);
        if (toastInstance && E.toast && E.toastMessage) {
            E.toast.className = `toast align-items-center border-0 text-white bg-${type}`;
            E.toastMessage.textContent = msg;
            toastInstance.show();
        } else {
            alert(msg);
        }
    }

    // ============================================================
    // 3. ESTADO GLOBAL
    // ============================================================
    let currentUser = null;
    let searchTimeout = null;
    let libraryData = [];
    let currentFilter = 'all';
    let currentSort = 'date';
    let currentLayout = 'grid';
    let searchQuery = '';

    // ============================================================
    // 4. API FETCH (COM credentials: 'include')
    // ============================================================
    async function apiFetch(url, options = {}) {
        log(`API ${options.method || 'GET'} ${url}`);
        try {
            const res = await fetch(url, {
                ...options,
                credentials: 'include',
                headers: { 'Content-Type': 'application/json', ...(options.headers || {}) }
            });
            const text = await res.text();
            log('Resposta bruta:', text);
            try { return JSON.parse(text); }
            catch (e) { throw new Error('Resposta inválida do servidor.'); }
        } catch (e) {
            logError('Erro na requisição:', e);
            throw new Error('Erro de conexão. Tente novamente.');
        }
    }

    // ============================================================
    // 5. RECOMENDAÇÕES
    // ============================================================
    async function loadRecommendations() {
        const section = document.getElementById('recommendations-section');
        const loading = document.getElementById('recommendations-loading');
        const list = document.getElementById('recommendations-list');
        const empty = document.getElementById('recommendations-empty');
        const basedOn = document.getElementById('recommendations-based-on');

        if (!currentUser) {
            if (section) section.style.display = 'none';
            return;
        }

        if (section) section.style.display = 'block';
        if (loading) loading.classList.remove('d-none');
        if (list) list.innerHTML = '';
        if (empty) empty.classList.add('d-none');
        if (basedOn) basedOn.textContent = '';

        try {
            const data = await apiFetch('../api/recommendations.php');
            if (data.error) {
                if (loading) loading.classList.add('d-none');
                if (empty) {
                    empty.classList.remove('d-none');
                    empty.innerHTML = `<i class="bi bi-emoji-frown"></i> ${data.error}`;
                }
                return;
            }

            if (loading) loading.classList.add('d-none');

            if (data.recommendations && data.recommendations.length > 0) {
                if (basedOn) basedOn.textContent = `(baseado em: ${data.based_on})`;
                list.innerHTML = data.recommendations.map(book => `
                    <div class="col">
                        <div class="card h-100 shadow-sm">
                            <img src="${book.thumbnail}" class="card-img-top book-cover" alt="${book.title}" loading="lazy">
                            <div class="card-body">
                                <h6 class="card-title">${book.title}</h6>
                                <p class="small text-muted">${book.authors}</p>
                                ${book.pageCount ? `<p class="small"><i class="fas fa-file-alt"></i> ${book.pageCount} páginas</p>` : ''}
                                ${book.price ? `<span class="badge bg-info text-dark">R$ ${book.price}</span>` : ''}
                            </div>
                            <div class="card-footer">
                                <button class="btn btn-primary btn-sm w-100 add-recommendation-btn" data-book='${JSON.stringify(book).replace(/'/g, "&#39;")}'>
                                    <i class="fas fa-plus-circle"></i> Adicionar
                                </button>
                            </div>
                        </div>
                    </div>
                `).join('');

                document.querySelectorAll('.add-recommendation-btn').forEach(btn => {
                    btn.addEventListener('click', async function() {
                        if (!currentUser) {
                            showToast('Faça login primeiro.', 'warning');
                            return;
                        }
                        const book = JSON.parse(this.dataset.book);
                        try {
                            const res = await apiFetch('../api/my-books.php', {
                                method: 'POST',
                                body: JSON.stringify({ ...book, status: 'want_to_read' })
                            });
                            if (res.success) {
                                showToast(res.message, 'success');
                                await loadMyLibrary();
                                await loadRecommendations();
                            } else {
                                showToast(res.error, 'danger');
                            }
                        } catch (e) {
                            showToast(e.message, 'danger');
                        }
                    });
                });

            } else {
                if (empty) {
                    empty.classList.remove('d-none');
                    empty.innerHTML = '<i class="bi bi-emoji-frown"></i> Nenhuma recomendação encontrada. Adicione mais livros com tags!';
                }
            }
        } catch (e) {
            if (loading) loading.classList.add('d-none');
            if (empty) {
                empty.classList.remove('d-none');
                empty.innerHTML = `<i class="bi bi-exclamation-triangle"></i> Erro: ${e.message}`;
            }
            logError('loadRecommendations erro:', e);
        }
    }

    // ============================================================
    // 6. AUTENTICAÇÃO (COM LINK DO PERFIL)
    // ============================================================
    function updateProfileLink() {
        if (E.btnProfile && currentUser) {
            E.btnProfile.href = `profile.html?user_id=${currentUser.id}`;
        }
    }

    async function checkAuth() {
        log('Verificando sessão...');
        try {
            const data = await apiFetch('../api/auth.php?action=me');
            log('checkAuth resposta:', data);
            if (data.logged) {
                currentUser = data.user;
                if (E.authButtons) E.authButtons.classList.add('d-none');
                if (E.userInfo) E.userInfo.classList.remove('d-none');
                if (E.userNameDisplay) E.userNameDisplay.textContent = currentUser.name;
                updateProfileLink();
                await loadMyLibrary();
                await loadRecommendations();
            } else {
                if (E.authButtons) E.authButtons.classList.remove('d-none');
                if (E.userInfo) E.userInfo.classList.add('d-none');
            }
        } catch (e) {
            logError('checkAuth erro:', e);
        }
    }

    async function handleLogin(email, password) {
        log('handleLogin:', email);
        try {
            const data = await apiFetch('../api/auth.php', {
                method: 'POST',
                body: JSON.stringify({ action: 'login', email, password })
            });
            log('Login resposta:', data);
            if (data.success) {
                currentUser = data.user;
                if (E.authButtons) E.authButtons.classList.add('d-none');
                if (E.userInfo) E.userInfo.classList.remove('d-none');
                if (E.userNameDisplay) E.userNameDisplay.textContent = currentUser.name;
                updateProfileLink();
                if (authModalInstance) authModalInstance.hide();
                if (E.authError) E.authError.textContent = '';
                showToast('Login realizado!', 'success');
                await loadMyLibrary();
                await loadRecommendations();
            } else {
                if (E.authError) E.authError.textContent = data.error || 'Falha no login.';
            }
        } catch (e) {
            if (E.authError) E.authError.textContent = e.message;
            logError('handleLogin erro:', e);
        }
    }

    async function handleRegister(name, email, password) {
        log('handleRegister:', name, email);
        try {
            const data = await apiFetch('../api/auth.php', {
                method: 'POST',
                body: JSON.stringify({ action: 'register', name, email, password })
            });
            log('Registro resposta:', data);
            if (data.success) {
                showToast('Cadastro realizado! Faça login.', 'success');
                toggleAuthMode('login');
                if (E.authForm) E.authForm.reset();
                if (E.authError) E.authError.textContent = '';
            } else {
                if (E.authError) E.authError.textContent = data.error || 'Falha no cadastro.';
            }
        } catch (e) {
            if (E.authError) E.authError.textContent = e.message;
            logError('handleRegister erro:', e);
        }
    }

    // ============================================================
    // 7. TOGGLE MODAL
    // ============================================================
    function toggleAuthMode(mode) {
        const isRegister = mode === 'register';
        if (E.registerFields) E.registerFields.classList.toggle('d-none', !isRegister);
        if (E.authSubmitBtn) {
            E.authSubmitBtn.innerHTML = isRegister
                ? '<i class="fas fa-user-plus me-2"></i>Cadastrar'
                : '<i class="fas fa-sign-in-alt me-2"></i>Entrar';
        }
        const label = document.getElementById('authModalLabel');
        if (label) label.textContent = isRegister ? 'Criar Conta' : 'Entrar';
        if (E.authError) E.authError.textContent = '';
        if (E.authForm) E.authForm.reset();
    }

    // ============================================================
    // 8. EVENTO DE CLIQUE NO BOTÃO DE SUBMIT
    // ============================================================
    function attachAuthClick() {
        const btn = document.getElementById('auth-submit-btn');
        if (!btn) {
            logError('❌ #auth-submit-btn não encontrado!');
            return;
        }

        log('✅ #auth-submit-btn encontrado. Anexando evento de clique...');

        const newBtn = btn.cloneNode(true);
        btn.parentNode.replaceChild(newBtn, btn);

        newBtn.addEventListener('click', async function(e) {
            e.preventDefault();
            log('🔹 BOTÃO DE SUBMIT CLICADO!');

            const email = E.authEmail ? E.authEmail.value.trim() : '';
            const password = E.authPassword ? E.authPassword.value : '';
            const isRegister = E.registerFields ? !E.registerFields.classList.contains('d-none') : false;

            log('📩 Dados:', { email, password, isRegister });

            if (!email || !password) {
                if (E.authError) E.authError.textContent = 'Preencha todos os campos.';
                return;
            }

            if (isRegister) {
                const name = E.regName ? E.regName.value.trim() : '';
                if (!name) {
                    if (E.authError) E.authError.textContent = 'Nome é obrigatório.';
                    return;
                }
                await handleRegister(name, email, password);
            } else {
                await handleLogin(email, password);
            }
        });

        E.authSubmitBtn = newBtn;
        log('✅ Evento de clique anexado com sucesso!');
    }

    // ============================================================
    // 9. BUSCA DE LIVROS (COM MÚLTIPLAS LOJAS)
    // ============================================================
    async function searchBooks(query) {
        const trimmed = query.trim();
        if (!trimmed) {
            if (E.searchResults) E.searchResults.innerHTML = '';
            return;
        }
        if (E.searchResults) {
            E.searchResults.innerHTML = `
                <div class="col-12 text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Carregando...</span>
                    </div>
                    <p class="mt-2 text-muted">Buscando livros...</p>
                </div>
            `;
        }
        try {
            const data = await apiFetch(`../api/books.php?q=${encodeURIComponent(trimmed)}`);
            if (data.error) {
                if (E.searchResults) E.searchResults.innerHTML = `<div class="col-12 text-danger">${data.error}</div>`;
                return;
            }
            if (data.length === 0) {
                if (E.searchResults) E.searchResults.innerHTML = '<div class="col-12 text-muted text-center">Nenhum livro encontrado.</div>';
                return;
            }
            if (E.searchResults) {
                E.searchResults.innerHTML = data.map(book => {
                    let buySection = '';
                    if (book.buyLink) {
                        const googleLink = `<a href="${book.buyLink}" target="_blank" class="btn btn-success btn-sm w-100 mb-1"><i class="fab fa-google-play"></i> Google Play ${book.price ? `R$ ${book.price}` : ''}</a>`;
                        const searchQuery = encodeURIComponent(`${book.title} ${book.authors}`);
                        const amazonLink = `<a href="https://www.amazon.com.br/s?k=${searchQuery}" target="_blank" class="btn btn-outline-secondary btn-sm w-100 mb-1"><i class="fab fa-amazon"></i> Amazon</a>`;
                        const submarinoLink = `<a href="https://www.submarino.com.br/busca?q=${searchQuery}" target="_blank" class="btn btn-outline-secondary btn-sm w-100 mb-1"><i class="fas fa-shopping-bag"></i> Submarino</a>`;
                        const americanasLink = `<a href="https://www.americanas.com.br/busca?q=${searchQuery}" target="_blank" class="btn btn-outline-secondary btn-sm w-100"><i class="fas fa-store"></i> Americanas</a>`;
                        buySection = `<div class="d-flex flex-column">${googleLink}${amazonLink}${submarinoLink}${americanasLink}</div>`;
                    } else {
                        buySection = `<span class="badge bg-secondary w-100">Indisponível para compra</span>`;
                    }

                    return `
                    <div class="col">
                        <div class="card h-100 shadow-sm">
                            <img src="${book.thumbnail}" class="card-img-top book-cover" alt="${book.title}" loading="lazy">
                            <div class="card-body">
                                <h6 class="card-title">${book.title}</h6>
                                <p class="small text-muted">${book.authors}</p>
                                ${book.publisher ? `<p class="small"><i class="fas fa-building"></i> ${book.publisher}</p>` : ''}
                                ${book.pageCount ? `<p class="small"><i class="fas fa-file-alt"></i> ${book.pageCount} páginas</p>` : ''}
                                ${book.buyLink && book.price ? `<span class="badge bg-info text-dark">R$ ${book.price}</span>` : ''}
                            </div>
                            <div class="card-footer d-flex flex-column gap-1">
                                <button class="btn btn-primary btn-sm w-100 add-book-btn" data-book='${JSON.stringify(book).replace(/'/g, "&#39;")}'>
                                    <i class="fas fa-plus-circle"></i> Adicionar
                                </button>
                                ${buySection}
                            </div>
                        </div>
                    </div>
                    `;
                }).join('');

                document.querySelectorAll('.add-book-btn').forEach(btn => {
                    btn.addEventListener('click', async function() {
                        if (!currentUser) {
                            showToast('Faça login primeiro.', 'warning');
                            return;
                        }
                        const book = JSON.parse(this.dataset.book);
                        try {
                            const res = await apiFetch('../api/my-books.php', {
                                method: 'POST',
                                body: JSON.stringify({ ...book, status: 'want_to_read' })
                            });
                            if (res.success) {
                                showToast(res.message, 'success');
                                await loadMyLibrary();
                            } else {
                                showToast(res.error, 'danger');
                            }
                        } catch (e) {
                            showToast(e.message, 'danger');
                        }
                    });
                });
            }
        } catch (e) {
            if (E.searchResults) E.searchResults.innerHTML = `<div class="col-12 text-danger">${e.message}</div>`;
            logError('searchBooks erro:', e);
        }
    }

    // ============================================================
    // 10. ESTANTE (COM BUSCA AVANÇADA)
    // ============================================================
    async function loadMyLibrary() {
        log('loadMyLibrary');
        if (!currentUser) {
            if (E.libraryDiv) E.libraryDiv.innerHTML = '';
            if (E.libraryEmpty) E.libraryEmpty.classList.remove('d-none');
            return;
        }
        try {
            const data = await apiFetch('../api/my-books.php');
            if (data.error) {
                if (E.libraryDiv) E.libraryDiv.innerHTML = `<div class="col-12 text-danger">${data.error}</div>`;
                return;
            }
            libraryData = data;
            renderLibrary();
        } catch (e) {
            if (E.libraryDiv) E.libraryDiv.innerHTML = `<div class="col-12 text-danger">${e.message}</div>`;
            logError('loadMyLibrary erro:', e);
        }
    }

    function renderLibrary() {
        let dataToRender = libraryData;
        if (searchQuery.trim()) {
            const q = searchQuery.trim().toLowerCase();
            dataToRender = libraryData.filter(book => {
                const statusPt = translateStatus(book.status).toLowerCase();
                const matchTitle = book.title.toLowerCase().includes(q);
                const matchAuthor = book.authors.toLowerCase().includes(q);
                const matchTags = book.tags ? book.tags.toLowerCase().includes(q) : false;
                const matchStatus = statusPt.includes(q);
                return matchTitle || matchAuthor || matchTags || matchStatus;
            });
        }

        if (E.filterTags && E.filterTags.value.trim()) {
            const tagQuery = E.filterTags.value.trim().toLowerCase();
            dataToRender = dataToRender.filter(book => {
                if (!book.tags) return false;
                return book.tags.toLowerCase().includes(tagQuery);
            });
        }

        const filtered = applyFiltersAndSort(dataToRender);
        updateStatusCounts(libraryData);
        if (E.bookCount) E.bookCount.textContent = libraryData.length;

        if (E.filteredCount) {
            E.filteredCount.textContent = `${filtered.length} livro${filtered.length !== 1 ? 's' : ''} encontrado${filtered.length !== 1 ? 's' : ''}`;
        }

        if (filtered.length === 0) {
            if (E.libraryDiv) E.libraryDiv.innerHTML = '';
            if (E.libraryEmpty) E.libraryEmpty.classList.remove('d-none');
            return;
        }
        if (E.libraryEmpty) E.libraryEmpty.classList.add('d-none');

        const layoutClass = currentLayout === 'grid'
            ? 'row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4'
            : 'row row-cols-1 g-3';

        if (E.libraryDiv) {
            E.libraryDiv.className = layoutClass;
            E.libraryDiv.innerHTML = filtered.map(ub => createBookCard(ub, currentLayout)).join('');
        }

        document.querySelectorAll('.edit-book-btn').forEach(btn => {
            btn.addEventListener('click', () => openReviewModal(btn.dataset.id));
        });

        document.querySelectorAll('.delete-book-btn').forEach(btn => {
            btn.addEventListener('click', async function() {
                if (!confirm('Remover este livro?')) return;
                try {
                    const res = await apiFetch('../api/my-books.php', {
                        method: 'DELETE',
                        body: JSON.stringify({ user_book_id: this.dataset.id })
                    });
                    if (res.success) {
                        showToast(res.message, 'success');
                        await loadMyLibrary();
                    } else {
                        showToast(res.error, 'danger');
                    }
                } catch (e) {
                    showToast(e.message, 'danger');
                }
            });
        });
    }

    function applyFiltersAndSort(data) {
        let result = [...data];
        if (currentFilter !== 'all') {
            result = result.filter(b => b.status === currentFilter);
        }
        switch (currentSort) {
            case 'title': result.sort((a, b) => a.title.localeCompare(b.title)); break;
            case 'rating': result.sort((a, b) => (b.rating || 0) - (a.rating || 0)); break;
            default: result.sort((a, b) => new Date(b.updated_at) - new Date(a.updated_at));
        }
        return result;
    }

    function updateStatusCounts(data) {
        const counts = { want_to_read: 0, reading: 0, finished: 0 };
        data.forEach(b => { if (counts.hasOwnProperty(b.status)) counts[b.status]++; });
        if (E.countWantToRead) E.countWantToRead.textContent = counts.want_to_read;
        if (E.countReading) E.countReading.textContent = counts.reading;
        if (E.countFinished) E.countFinished.textContent = counts.finished;
    }

    function createBookCard(ub, layout) {
        const statusLabel = translateStatus(ub.status);
        const statusBadge = `bg-${getStatusBadge(ub.status)}`;
        const statusBorder = `border-${getStatusColor(ub.status)}`;

        let dateInfo = '';
        if (ub.started_at) {
            dateInfo += `<p class="small text-muted"><i class="bi bi-calendar-plus"></i> Início: ${formatDate(ub.started_at)}</p>`;
        }
        if (ub.finished_at) {
            dateInfo += `<p class="small text-muted"><i class="bi bi-calendar-check"></i> Término: ${formatDate(ub.finished_at)}</p>`;
        }

        let tagsHtml = '';
        if (ub.tags) {
            const tags = ub.tags.split(',').map(t => t.trim()).filter(t => t);
            if (tags.length) {
                tagsHtml = `<div class="mt-1">${tags.map(t => `<span class="badge bg-secondary me-1">${t}</span>`).join('')}</div>`;
            }
        }

        let reminder = '';
        if (ub.status === 'reading' && ub.updated_at) {
            const updated = new Date(ub.updated_at);
            const now = new Date();
            const diffDays = Math.floor((now - updated) / (1000 * 60 * 60 * 24));
            if (diffDays > 7) {
                reminder = `<span class="badge bg-danger ms-1"><i class="bi bi-alarm"></i> ${diffDays} dias</span>`;
            }
        }

        let extra = '';
        if (ub.rating) extra += `<span class="badge bg-warning text-dark"><i class="bi bi-star-fill"></i> ${ub.rating}</span> `;
        if (ub.review) {
            const short = ub.review.length > 60 ? ub.review.substring(0, 60) + '…' : ub.review;
            extra += `<p class="small mt-2"><em>"${short}"</em></p>`;
        }

        let progress = '';
        if (ub.status === 'reading' && ub.pageCount && ub.pageCount > 0) {
            const progressPercent = Math.min(100, Math.round((ub.current_page || 0) / ub.pageCount * 100));
            progress = `
                <div class="progress mt-2" style="height:6px;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" style="width:${progressPercent}%"></div>
                </div>
                <p class="small text-muted">${ub.current_page || 0} / ${ub.pageCount} páginas</p>
            `;
        }

        const cardHtml = `
            <div class="card h-100 shadow-sm ${statusBorder}">
                <img src="${ub.thumbnail}" class="card-img-top book-cover" alt="${ub.title}" loading="lazy">
                <div class="card-body">
                    <h6 class="card-title">${ub.title}</h6>
                    <span class="badge ${statusBadge}">${statusLabel}</span>
                    ${reminder}
                    ${tagsHtml}
                    ${dateInfo}
                    ${extra}
                    ${progress}
                </div>
                <div class="card-footer d-flex gap-1">
                    <button class="btn btn-sm btn-outline-secondary edit-book-btn" data-id="${ub.user_book_id}"><i class="bi bi-pencil"></i></button>
                    <button class="btn btn-sm btn-outline-danger delete-book-btn" data-id="${ub.user_book_id}"><i class="bi bi-trash"></i></button>
                </div>
            </div>
        `;

        if (layout === 'grid') {
            return `<div class="col">${cardHtml}</div>`;
        } else {
            return `<div class="col">${cardHtml}</div>`;
        }
    }

    function formatDate(dateStr) {
        if (!dateStr) return '';
        const d = new Date(dateStr + 'T00:00:00');
        return d.toLocaleDateString('pt-BR');
    }

    // ============================================================
    // 11. REVIEW MODAL (COM DATAS E TAGS)
    // ============================================================
    async function openReviewModal(userBookId) {
        if (E.reviewUserBookId) E.reviewUserBookId.value = userBookId;

        try {
            const data = await apiFetch('../api/my-books.php');
            const book = data.find(b => b.user_book_id == userBookId);
            if (book) {
                if (E.reviewStatus) E.reviewStatus.value = book.status || 'want_to_read';
                if (E.reviewRating) E.reviewRating.value = book.rating || '';
                if (E.reviewText) E.reviewText.value = book.review || '';
                if (E.reviewStartedAt) E.reviewStartedAt.value = book.started_at || '';
                if (E.reviewFinishedAt) E.reviewFinishedAt.value = book.finished_at || '';
                if (E.reviewTags) E.reviewTags.value = book.tags || '';
            }
        } catch (e) {
            logError('Erro ao carregar dados do livro:', e);
        }

        if (reviewModalInstance) reviewModalInstance.show();
    }

    if (E.saveReviewBtn) {
        E.saveReviewBtn.addEventListener('click', async function() {
            const id = E.reviewUserBookId ? E.reviewUserBookId.value : null;
            const status = E.reviewStatus ? E.reviewStatus.value : 'want_to_read';
            const rating = E.reviewRating ? E.reviewRating.value : null;
            const review = E.reviewText ? E.reviewText.value.trim() : '';
            const started_at = E.reviewStartedAt ? E.reviewStartedAt.value : null;
            const finished_at = E.reviewFinishedAt ? E.reviewFinishedAt.value : null;
            const tags = E.reviewTags ? E.reviewTags.value.trim() : '';

            if (!id) { showToast('Erro: livro não identificado.', 'danger'); return; }

            try {
                const res = await apiFetch('../api/my-books.php', {
                    method: 'PUT',
                    body: JSON.stringify({
                        user_book_id: id,
                        status,
                        rating,
                        review,
                        started_at,
                        finished_at,
                        tags
                    })
                });
                if (res.success) {
                    showToast(res.message, 'success');
                    if (reviewModalInstance) reviewModalInstance.hide();
                    await loadMyLibrary();
                } else {
                    showToast(res.error, 'danger');
                }
            } catch (e) {
                showToast(e.message, 'danger');
            }
        });
    }

    // ============================================================
    // 12. HELPERS
    // ============================================================
    function getStatusColor(s) {
        const map = { 'want_to_read': 'primary', 'reading': 'warning', 'finished': 'success' };
        return map[s] || 'secondary';
    }
    function getStatusBadge(s) {
        const map = { 'want_to_read': 'primary', 'reading': 'warning', 'finished': 'success' };
        return map[s] || 'secondary';
    }
    function translateStatus(s) {
        const map = { 'want_to_read': 'Quero Ler', 'reading': 'Lendo', 'finished': 'Concluído' };
        return map[s] || s;
    }

    // ============================================================
    // 13. EVENTOS DE UI
    // ============================================================

    if (E.btnLogin) E.btnLogin.addEventListener('click', () => toggleAuthMode('login'));
    if (E.btnRegister) E.btnRegister.addEventListener('click', () => toggleAuthMode('register'));

    if (E.btnLogout) {
        E.btnLogout.addEventListener('click', async function() {
            try {
                await apiFetch('../api/auth.php', { method: 'POST', body: JSON.stringify({ action: 'logout' }) });
                showToast('Logout realizado.', 'info');
                setTimeout(() => location.reload(), 500);
            } catch (e) { showToast(e.message, 'danger'); }
        });
    }

    if (E.searchInput) {
        E.searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const q = this.value;
            if (q.length > 2) {
                searchTimeout = setTimeout(() => searchBooks(q), 600);
            } else if (q.length === 0) {
                if (E.searchResults) E.searchResults.innerHTML = '';
            }
        });
    }
    if (E.searchBtn) {
        E.searchBtn.addEventListener('click', () => {
            if (E.searchInput) searchBooks(E.searchInput.value);
        });
    }

    if (E.filterTags) {
        E.filterTags.addEventListener('input', function() {
            renderLibrary();
        });
    }

    if (E.searchLibrary) {
        E.searchLibrary.addEventListener('input', function() {
            searchQuery = this.value;
            renderLibrary();
        });
    }

    if (E.clearSearchBtn) {
        E.clearSearchBtn.addEventListener('click', function() {
            if (E.searchLibrary) {
                E.searchLibrary.value = '';
                searchQuery = '';
                renderLibrary();
            }
        });
    }

    document.querySelectorAll('[data-filter]').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('[data-filter]').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            currentFilter = this.dataset.filter;
            renderLibrary();
        });
    });

    document.querySelectorAll('[data-sort]').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('[data-sort]').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            currentSort = this.dataset.sort;
            renderLibrary();
        });
    });

    document.querySelectorAll('[data-layout]').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('[data-layout]').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            currentLayout = this.dataset.layout;
            renderLibrary();
        });
    });

    // ============================================================
    // 14. INICIALIZAÇÃO
    // ============================================================
    attachAuthClick();
    checkAuth();

    log('✅ Aplicação pronta!');
})();
