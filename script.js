let rawData = {};
let currentStudent = null;
let currentPage = 'dashboard';

async function initApp() {
    showToast("Đang kết nối hệ thống...", "info");
    await loadData();
    
    const sInput = document.querySelector('.search-box input');
    if(sInput) {
        sInput.addEventListener('keypress', (e) => {
            if(e.key === 'Enter') {
                const k = e.target.value.toLowerCase().trim();
                if(['điểm', 'kết quả'].some(w=>k.includes(w))) navigate('progress');
                else if(['lịch', 'hôm nay'].some(w=>k.includes(w))) navigate('schedule');
                else if(['khóa'].some(w=>k.includes(w))) navigate('courses');
                else if(['tiền', 'học phí'].some(w=>k.includes(w))) navigate('finance');
                else if(['con', 'thêm'].some(w=>k.includes(w))) navigate('students');
                else if(['zalo', 'liên hệ'].some(w=>k.includes(w))) navigate('chat');
                else showToast("Không tìm thấy kết quả", "error");
                e.target.value = '';
            }
        });
    }

    document.addEventListener('click', (e) => {
        const panel = document.getElementById('notif-panel');
        const btn = document.querySelector('.notif-btn');
        if(panel && panel.classList.contains('open') && !panel.contains(e.target) && !btn.contains(e.target)) {
            panel.classList.remove('open');
        }
    });
}

async function loadData() {
    try {
        const response = await fetch('api.php');
        rawData = await response.json();
        
        const studentCodes = Object.keys(rawData.students);
        if(studentCodes.length > 0) {
            if(!currentStudent || !rawData.students[currentStudent]) {
                currentStudent = studentCodes[0]; 
            }
            renderSidebarStudents();
            renderNotifications();
            navigate(currentPage);
        } else {
            document.getElementById('content').innerHTML = '<div class="card" style="text-align:center; padding: 40px">Chưa có học sinh nào. Hãy thêm học sinh!</div>';
        }
    } catch (e) { 
        showToast("Lỗi kết nối cơ sở dữ liệu!", "error");
    }
}

function renderSidebarStudents() {
    const container = document.getElementById('student-list-sidebar');
    if(!container) return;
    container.innerHTML = '';
    
    Object.keys(rawData.students).forEach(code => {
        const s = rawData.students[code];
        const initial = s.name.charAt(0).toUpperCase();
        const isActive = code === currentStudent ? 'active' : '';
        const color = code === currentStudent ? '#4f9cf9' : '#e8f0fd';
        const txtColor = code === currentStudent ? '#fff' : 'var(--accent)';
        
        container.innerHTML += `
            <button class="student-btn ${isActive}" onclick="switchStudent('${code}')">
                <div class="student-avatar" style="background:${color}; color:${txtColor}">${initial}</div>
                <div><div class="s-name">${s.name}</div><div class="s-class">Lớp ${s.class}</div></div>
            </button>`;
    });
}

function renderNotifications() {
    const container = document.getElementById('notif-list');
    const dot = document.getElementById('notif-dot');
    if(!container) return;
    const notifs = rawData.notifications || [];
    
    let unread = 0;
    container.innerHTML = notifs.map(n => {
        if(n.is_read == 0) unread++;
        const icon = n.type === 'warning' ? '⚠' : (n.type === 'success' ? '✓' : 'i');
        const bg = n.type === 'warning' ? '#fef5e0' : (n.type === 'success' ? '#e3f9f2' : '#e8f0fd');
        const col = n.type === 'warning' ? '#b07d00' : (n.type === 'success' ? '#1a9070' : '#3b7dd8');
        return `
        <div class="notif-item ${n.is_read==0 ? 'unread' : ''}">
            <div class="notif-icon" style="background:${bg}; color:${col}; font-weight:bold">${icon}</div>
            <div><div class="notif-text">${n.title}</div><div class="notif-time">${n.time_ago || ''}</div></div>
        </div>`;
    }).join('');
    
    if(dot) dot.style.display = unread > 0 ? 'block' : 'none';
}

async function submitAddStudent(e) {
    e.preventDefault();
    const btn = document.getElementById('btn-submit-student');
    btn.innerHTML = "Đang lưu...";
    btn.disabled = true;

    const payload = {
        action: 'add_student',
        name: document.getElementById('inp-name').value,
        dob: document.getElementById('inp-dob').value,
        class: document.getElementById('inp-class').value,
        school: document.getElementById('inp-school').value
    };

    try {
        const response = await fetch('api.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
        const result = await response.json();
        if(result.status === 'success') {
            showToast(result.message, "success");
            closeModal('modal-student');
            document.getElementById('addStudentForm').reset();
            await loadData();
        } else { showToast(result.message, "error"); }
    } catch(err) { showToast("Lỗi hệ thống!", "error"); }
    
    btn.innerHTML = "Lưu học sinh";
    btn.disabled = false;
}

function switchStudent(code) {
    currentStudent = code;
    renderSidebarStudents();
    renderPage(currentPage);
    if(window.innerWidth <= 768) toggleMobileMenu();
}

function navigate(page, el) {
    currentPage = page;
    document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
    if(el) el.classList.add('active');
    else {
        const nav = document.querySelector(`.nav-item[data-page="${page}"]`);
        if(nav) nav.classList.add('active');
    }
    renderPage(page);
    if(window.innerWidth <= 768 && el) toggleMobileMenu();
}

function toggleMobileMenu() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('mobile-overlay');
    if(sidebar) sidebar.classList.toggle('open');
    if(overlay) overlay.classList.toggle('open');
}

function showToast(message, type = "success") {
    const container = document.getElementById('toast-container');
    if(!container) return;
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.innerHTML = `${type === 'success' ? '✅' : '❌'} ${message}`;
    container.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
}

function renderPage(page) {
    const s = rawData.students[currentStudent];
    const c = document.getElementById('content');
    
    if(!s) { c.innerHTML = '<div class="card">Đang cập nhật...</div>'; return; }
    
    const titles = { dashboard:'Tổng quan', progress:'Kết quả học tập', schedule:'Thời khóa biểu', courses:'Khóa học', finance:'Tài chính', chat:'Liên hệ Zalo', students:'Quản lý học sinh' };
    document.getElementById('page-title').textContent = titles[page] || page;
    document.getElementById('page-sub').textContent = `Học sinh: ${s.name} - Lớp: ${s.class}`;
    
    c.innerHTML = `<div class="page-content">${getPageHTML(page, s)}</div>`;
    if(page === 'dashboard') setTimeout(() => initCharts(s), 50);
}

function getPageHTML(page, s) {
    const formatMoney = (num) => {
        const val = parseFloat(num);
        return isNaN(val) ? '0 đ' : new Intl.NumberFormat('vi-VN').format(val) + ' đ';
    };

    if(page === 'dashboard') {
        // XỬ LÝ HIỂN THỊ N/A NẾU CHƯA CÓ DỮ LIỆU
        const gpaDisplay = s.gpa !== null ? s.gpa : "N/A";
        const attDisplay = s.attendance !== null ? s.attendance + "%" : "N/A";
        
        const todaySched = (s.schedule['T2'] || []).map(i => `<div class="schedule-slot"><small style="color:var(--accent); font-weight:700">${i.start_end_time}</small><br><b>${i.subject_name}</b></div>`).join('') || "Hôm nay nghỉ học";
        
        return `
        <div class="stats-grid">
            <div class="stat-card"><div class="stat-num">${gpaDisplay}</div><div class="stat-label">Điểm TB</div></div>
            <div class="stat-card"><div class="stat-num">${attDisplay}</div><div class="stat-label">Chuyên cần</div></div>
            <div class="stat-card"><div class="stat-num">${formatMoney(s.finance.due)}</div><div class="stat-label">Học phí nợ</div></div>
        </div>
        <div class="two-col">
            <div class="card"><div class="card-header">Biến động điểm</div><div class="chart-bar-wrap" id="chart-monthly"></div></div>
            <div class="card"><div class="card-header">Lịch học hôm nay</div><div>${todaySched}</div></div>
        </div>`;
    }

    if(page === 'progress') {
        const rows = s.subjects.map(sub => `<tr><td><b>${sub.subject_name}</b></td><td>${sub.prev_score}</td><td><b>${sub.current_score}</b></td><td><span class="badge blue">${sub.status}</span></td></tr>`).join('');
        return `<div class="card"><div class="card-header">Bảng điểm</div><table><thead><tr><th>Môn</th><th>Kỳ trước</th><th>Hiện tại</th><th>Xếp loại</th></tr></thead><tbody>${rows || '<tr><td colspan="4" style="text-align:center">Chưa có dữ liệu điểm</td></tr>'}</tbody></table></div>`;
    }

    if(page === 'schedule') {
        const days = ['T2','T3','T4','T5','T6','T7'];
        const cols = days.map(d => `<div class="day-col"><div class="day-label">${d}</div>${(s.schedule[d] || []).map(i => `<div class="schedule-slot ${i.slot_color}"><b>${i.subject_name}</b></div>`).join('')}</div>`).join('');
        return `<div class="card"><div class="card-header">Thời khóa biểu</div><div class="schedule-grid">${cols}</div></div>`;
    }

    if(page === 'courses') {
        const myCourses = s.courses.map(c => {
            const prog = c.lessons.length > 0 ? 100 : 0; 
            return `
            <div class="card" style="margin-bottom:16px;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                    <h3 style="color:var(--accent)">${c.course_name}</h3>
                    <span class="badge blue">Đang học</span>
                </div>
                <div class="progress-bar" style="margin-bottom:10px;">
                    <div class="progress-fill" style="width: ${prog}%"></div>
                </div>
                <div style="font-size:12px; color:var(--text-muted)">Gồm ${c.lessons.length} bài học</div>
            </div>`;
        }).join('');

        const available = (rawData.available_courses || []).map(a => `
            <div style="padding:12px; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center;">
                <div><div style="font-weight:600">${a.course_name}</div><div style="font-size:11px; color:var(--text-muted)">Giá: ${formatMoney(a.price)}</div></div>
                <button class="btn btn-ghost" style="padding:4px 10px; font-size:11px;">Xem</button>
            </div>`).join('');

        return `<div class="two-col">
                    <div><div class="card-header">Khóa học của con</div>${myCourses || '<div class="card" style="text-align:center; padding:20px; color:var(--text-muted)">Chưa đăng ký khóa học nào</div>'}</div>
                    <div class="card"><div class="card-header">Khóa học gợi ý</div>${available || 'Đang tải...'}</div>
                </div>`;
    }

    if(page === 'finance') {
        return (() => {
            const history = s.finance.history.map(h => `<tr><td>${h.payment_date || '---'}</td><td>${h.description}</td><td style="font-weight:600">${formatMoney(h.amount)}</td><td><span class="badge ${h.status==='paid'?'green':'blue'}">${h.status==='paid'?'Đã thu':'Chưa thanh toán'}</span></td></tr>`).join('');
            return `
            <div class="stats-grid">
                <div class="stat-card" style="border-top:4px solid var(--success)"><div class="stat-num" style="color:var(--success)">${formatMoney(s.finance.paid)}</div><div class="stat-label">Đã đóng</div></div>
                <div class="stat-card" style="border-top:4px solid #e05252"><div class="stat-num" style="color:#e05252">${formatMoney(s.finance.due)}</div><div class="stat-label">Chưa thanh toán</div></div>
            </div>
            <div class="card"><div class="card-header">Lịch sử tài chính</div><table><thead><tr><th>Ngày</th><th>Nội dung</th><th>Số tiền</th><th>Trạng thái</th></tr></thead><tbody>${history || '<tr><td colspan="4" style="text-align:center">Không có dữ liệu giao dịch</td></tr>'}</tbody></table></div>`;
        })();
        const history = s.finance.history.map(h => `<tr><td>${h.payment_date || '---'}</td><td>${h.description}</td><td style="font-weight:600">${formatMoney(h.amount)}</td><td><span class="badge ${h.status==='paid'?'green':'blue'}">${h.status==='paid'?'Đã thu':'Nợ'}</span></td></tr>`).join('');
        return `
            <div class="stats-grid">
                <div class="stat-card" style="border-top:4px solid var(--success)"><div class="stat-num" style="color:var(--success)">${formatMoney(s.finance.paid)}</div><div class="stat-label">Đã đóng</div></div>
                <div class="stat-card" style="border-top:4px solid #e05252"><div class="stat-num" style="color:#e05252">${formatMoney(s.finance.due)}</div><div class="stat-label">Còn nợ</div></div>
            </div>
            <div class="card"><div class="card-header">Lịch sử tài chính</div><table><thead><tr><th>Ngày</th><th>Nội dung</th><th>Số tiền</th><th>Trạng thái</th></tr></thead><tbody>${history || '<tr><td colspan="4" style="text-align:center">Không có dữ liệu giao dịch</td></tr>'}</tbody></table></div>`;
    }

    if(page === 'chat') return `
        <div class="card" style="text-align:center; padding:60px; max-width:500px; margin:40px auto">
            <img src="https://upload.wikimedia.org/wikipedia/commons/9/91/Icon_of_Zalo.svg" style="width:90px; margin:0 auto 24px; display:block;" />
            <h2 style="margin-bottom:12px">Liên hệ Zalo</h2>
            <a href="https://zalo.me/0343194121" target="_blank"><button class="btn btn-primary" style="border-radius:99px;">Nhắn Zalo Ngay</button></a>
        </div>`;

    if(page === 'students') return `
        <div class="card">
            <div style="display:flex; justify-content:space-between; margin-bottom:20px">
                <h2>Hồ sơ học sinh</h2>
                <button class="btn btn-primary" onclick="openModal('modal-student')">+ Thêm học sinh</button>
            </div>
            <div style="padding:24px; background:var(--surface2); border-radius:16px;">
                <h3>${s.name}</h3>
                <p style="margin-top:8px; color:var(--text-muted)">Lớp: ${s.class} | Trường: ${s.school} | Ngày sinh: ${s.dob}</p>
            </div>
        </div>`;
}

// XÓA TẬN GỐC DỮ LIỆU CŨ KHI VẼ BIỂU ĐỒ
function initCharts(s) {
    const el = document.getElementById('chart-monthly');
    if(!el) return;
    
    const scores = s.monthlyScores || [];
    
    if (scores.length === 0) {
        el.innerHTML = '<div style="width:100%; text-align:center; color:var(--text-muted); align-self:center; margin-top: 40px; font-weight: 500;">Chưa có dữ liệu biểu đồ</div>';
        return;
    }

    el.innerHTML = scores.map(v => `<div class="chart-bar-col"><div class="chart-bar-val">${v}</div><div class="chart-bar" style="height:${(v/10)*100}%"></div></div>`).join('');
}

function toggleNotif() { 
    const panel = document.getElementById('notif-panel');
    if(panel) panel.classList.toggle('open'); 
}

function openModal(id) { 
    const modal = document.getElementById(id);
    if(modal) modal.classList.add('open'); 
}

function closeModal(id) { 
    const modal = document.getElementById(id);
    if(modal) modal.classList.remove('open');
}

initApp();
