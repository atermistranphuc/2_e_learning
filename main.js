// ==========================================
// 1. CHẾ ĐỘ TEST (BỎ QUA YÊU CẦU ĐĂNG NHẬP ĐỂ CODE GIAO DIỆN)
// ==========================================
let currentUser = JSON.parse(localStorage.getItem('currentUser'));

if (!currentUser) {
    currentUser = { username: 'student_test', name: 'Nguyễn Văn An (Test)', role: 'student' };
}

// ==========================================
// 2. LOGIC HOẠT ĐỘNG CHÍNH
// ==========================================
let myChart = null;
const getDB = () => JSON.parse(localStorage.getItem('portal_db')) || { scores: [], progress: {}, joinedCourses: [] };
const setDB = (db) => localStorage.setItem('portal_db', JSON.stringify(db));

function loadJoinedCoursesFromDB() {
    const db = getDB();
    if (db.joinedCourses && db.joinedCourses.length > 0) {
        db.joinedCourses.forEach(joinedId => {
            if (!COURSES_DATA.some(c => c.id === joinedId)) {
                const sysCourse = SYSTEM_DATABASE_COURSES.find(c => c.id === joinedId);
                if (sysCourse) COURSES_DATA.push(sysCourse);
            }
        });
    }
}

const calculateGPA = () => {
    const db = getDB();
    if (db.scores.length === 0) return "0.00";
    return (db.scores.reduce((sum, s) => sum + s.score, 0) / db.scores.length).toFixed(2);
};

document.addEventListener('DOMContentLoaded', () => {
    if(currentUser) {
        document.getElementById('user-name').textContent = currentUser.name;
        document.getElementById('user-avatar').textContent = currentUser.name.charAt(0).toUpperCase();
        document.getElementById('page-sub').textContent = `Chào mừng trở lại, ${currentUser.name}!`;
    }

    loadJoinedCoursesFromDB();
    lucide.createIcons();
    renderPage('dashboard');
    setupNotif();
    
    document.querySelectorAll('.nav-item').forEach(btn => {
        btn.onclick = function() {
            document.querySelectorAll('.nav-item').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            renderPage(this.dataset.page);
        };
    });
});

function setupNotif() {
    const btn = document.getElementById('notif-btn');
    const panel = document.getElementById('notif-panel');
    const list = document.getElementById('notif-list');
    if(!btn) return;
    btn.onclick = (e) => { 
        e.stopPropagation(); 
        panel.classList.toggle('open'); 
        const db = getDB();
        let notifHTML = `<div class="notif-item"><div class="notif-icon" style="background:#e8f0fd; color:#3b7dd8;"><i data-lucide="info" style="width:16px;"></i></div><div><div class="notif-text">Bạn đã sẵn sàng học tập!</div><div class="notif-time">Hệ thống</div></div></div>`;
        if (db.scores.length > 0) {
            const last = db.scores[db.scores.length - 1];
            notifHTML += `<div class="notif-item"><div class="notif-icon" style="background:#e3f9f2; color:#1a9070;"><i data-lucide="check" style="width:16px;"></i></div><div><div class="notif-text">Bài '${last.name}' đạt ${last.score}đ</div><div class="notif-time">Mới cập nhật</div></div></div>`;
        }
        list.innerHTML = notifHTML;
        lucide.createIcons();
    };
    document.onclick = () => panel.classList.remove('open');
    panel.onclick = (e) => e.stopPropagation();
}

function renderPage(page) {
    const view = document.getElementById('content');
    const db = getDB();
    const titles = {dashboard: 'Tóm tắt học tập', courses: 'Khóa học của tôi', assignments: 'Bài tập của tôi', progress: 'Kết quả học tập'};
    document.getElementById('page-title').textContent = titles[page] || '';

    if (page === 'dashboard') {
        const pending = ASSIGNMENTS_DATA.filter(a => a.status === 'Chưa làm');
        view.innerHTML = `
            <div class="animate-fade-in">
                <div class="stats-grid">
                    <div class="stat-card blue"><div class="stat-icon blue"><i data-lucide="award"></i></div><div class="stat-num">${calculateGPA()}</div><div class="stat-label">Điểm trung bình (GPA)</div></div>
                    <div class="stat-card green"><div class="stat-icon green"><i data-lucide="trending-up"></i></div><div class="stat-num">${db.scores.length}</div><div class="stat-label">Bài đã nộp</div></div>
                    <div class="card" style="grid-column: span 2; margin: 0;"><div class="card-body" style="padding-top:12px;"><div style="height: 90px; width: 100%;"><canvas id="myChart"></canvas></div></div></div>
                </div>
                <div class="two-col" style="margin-top: 24px;">
                    <div class="card">
                        <div class="card-header"><div class="card-title">Bài tập chưa làm</div></div>
                        <div class="card-body" style="padding:12px;">
                            ${pending.length === 0 ? `<div style="text-align:center; padding:30px; color:var(--text3);">Tuyệt vời, đã xong hết bài tập!</div>` : 
                            pending.map(q => `<div style="display:flex; justify-content:space-between; align-items:center; padding:12px 16px; border-bottom:1px solid var(--border);"><span style="font-weight:600;">${q.title}</span><button class="btn btn-primary btn-sm" onclick="renderPage('assignments')">Xem</button></div>`).join('')}
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header"><div class="card-title">Lịch học</div></div>
                        <div class="card-body">
                            ${SCHEDULE_DATA.map(s => `<div style="display:flex; align-items:center; gap:12px; padding:12px; background:var(--surface2); border-radius:10px; margin-bottom:12px;"><div style="font-size:11px; color:var(--text3); font-weight:600;">${s.time}</div><div style="font-weight:600;">${s.subject}</div><span class="badge blue" style="margin-left:auto;">${s.room}</span></div>`).join('')}
                        </div>
                    </div>
                </div>
            </div>`;
        setTimeout(initChart, 100);

    } else if (page === 'assignments') {
        view.innerHTML = `
            <div class="two-col animate-fade-in" style="grid-template-columns: 2.5fr 1fr;">
                <div>
                    <div class="tabs-container">
                        <button class="tab-pill active" onclick="filterAssign('Tất cả', this)">Tất cả</button>
                        <button class="tab-pill" onclick="filterAssign('Chưa làm', this)">Chưa làm</button>
                        <button class="tab-pill" onclick="filterAssign('Đã hoàn thành', this)">Đã hoàn thành</button>
                    </div>
                    <div id="assignment-list">${renderAssignList('Tất cả')}</div>
                </div>
                <div class="card h-fit">
                    <div class="card-header"><div class="card-title">Lịch học tập</div></div>
                    <div class="card-body">
                        <div class="cal-header"><i data-lucide="chevron-left" style="width:16px;"></i><span>Tháng 4, 2026</span><i data-lucide="chevron-right" style="width:16px;"></i></div>
                        <div class="cal-grid" style="margin-bottom:20px;">
                            <div class="cal-day-name">T2</div><div class="cal-day-name">T3</div><div class="cal-day-name">T4</div><div class="cal-day-name">T5</div><div class="cal-day-name">T6</div><div class="cal-day-name">T7</div><div class="cal-day-name">CN</div>
                            <div></div><div></div><div></div><div class="cal-day">1</div><div class="cal-day">2</div><div class="cal-day event">3</div><div class="cal-day">4</div>
                            <div class="cal-day">5</div><div class="cal-day">6</div><div class="cal-day">7</div><div class="cal-day">8</div><div class="cal-day active">9</div><div class="cal-day">10</div><div class="cal-day event">11</div>
                        </div>
                    </div>
                </div>
            </div>`;

    } else if (page === 'courses') {
        view.innerHTML = `
            <div class="three-col animate-fade-in">
                ${COURSES_DATA.map(c => `
                <div class="card" style="padding: 24px;">
                    <div style="background:#2357b3; color:#fff; padding:16px; border-radius:12px; margin-bottom:16px;">
                        <h4 style="font-weight:700; font-size:16px;">${c.title}</h4>
                        <div style="font-size:11px;">GV: ${c.teacher}</div>
                    </div>
                    <div class="flex justify-between items-center mb-2" style="font-size:11px; font-weight:600;"><span>Tiến độ</span><span style="color:#2357b3;">${c.progress}%</span></div>
                    <div class="progress-bar mb-5"><div class="progress-fill" style="width:${c.progress}%; background:#f0a500;"></div></div>
                    <button class="btn btn-primary" style="width:100%; justify-content:center;" onclick="renderCourseDetail(${c.id})">Vào học ngay</button>
                </div>`).join('')}
            </div>`;

    } else if (page === 'progress') {
        view.innerHTML = `<div class="card animate-fade-in"><div class="card-header"><div class="card-title">Lịch sử bài tập</div></div><table><thead><tr><th>Tên bài tập</th><th>Điểm số</th><th>Ngày nộp</th></tr></thead><tbody>${db.scores.length === 0 ? `<tr><td colspan="3" style="text-align:center; padding:40px; color:var(--text3);">Chưa có kết quả.</td></tr>` : db.scores.map(s => `<tr><td style="font-weight:600;">${s.name}</td><td><strong>${s.score}</strong></td><td style="color:var(--text2);">${s.date}</td></tr>`).reverse().join('')}</tbody></table></div>`;
    }
    lucide.createIcons();
}

function filterAssign(status, btnElement) {
    document.querySelectorAll('.tab-pill').forEach(b => b.classList.remove('active'));
    btnElement.classList.add('active');
    document.getElementById('assignment-list').innerHTML = renderAssignList(status);
    lucide.createIcons();
}

function renderAssignList(status) {
    const list = status === 'Tất cả' ? ASSIGNMENTS_DATA : ASSIGNMENTS_DATA.filter(a => a.status === status);
    if(list.length === 0) return `<div style="text-align:center; padding:60px; color:var(--text3);">Không có bài tập nào.</div>`;
    return list.map(a => `
        <div class="assignment-card">
            <div><div style="font-weight:700; font-size:15px; margin-bottom:6px;">${a.title}</div><div style="font-size:12px; color:var(--text2);">Hạn nộp: ${a.deadline}</div></div>
            <div style="display:flex; align-items:center; gap:24px;">
                <div style="text-align:center; width:90px;"><span class="badge ${a.status === 'Chưa làm' ? 'red' : 'green'}">${a.status}</span></div>
                <button class="btn btn-primary" onclick="renderCourseDetail(${a.courseId}); setTimeout(()=>renderLessonInDetail(${a.courseId}, 'c1', ${a.lessonId}), 100)"><i data-lucide="play" style="width:14px;"></i> Làm ngay</button>
            </div>
        </div>`).join('');
}

function renderCourseDetail(courseId) {
    const course = COURSES_DATA.find(c => c.id === courseId);
    const view = document.getElementById('content');
    
    view.innerHTML = `
        <div class="two-col animate-fade-in" style="grid-template-columns: 1fr 2.5fr;">
            <div>
                <div style="background:#2357b3; color:#fff; padding:20px; border-radius:12px; margin-bottom:16px;">
                    <h3 style="font-size:16px; font-weight:700; margin:0 0 5px 0;">${course.title}</h3>
                    <div style="font-size:11px; margin-bottom:16px;">${course.teacher}</div>
                    <div class="progress-bar"><div class="progress-fill" style="width:${course.progress}%; background:#f0a500;"></div></div>
                </div>
                <div>
                    ${course.chapters.map((chap, idx) => `
                    <div class="chapter-item">
                        <div class="chapter-header active" onclick="this.classList.toggle('active')">
                            <div style="display:flex; gap:12px; align-items:center;">
                                <div style="width:28px; height:28px; background:rgba(0,0,0,0.05); border-radius:6px; display:flex; align-items:center; justify-content:center;"><i data-lucide="book" style="width:14px;"></i></div>
                                <div><div style="font-size:13px;">${chap.title}</div></div>
                            </div>
                        </div>
                        <div class="chapter-body">
                            ${chap.lessons.map(l => `<div class="lesson-row" onclick="renderLessonInDetail(${course.id}, '${chap.id}', ${l.id})"><i data-lucide="${l.type === 'video' ? 'play-circle' : l.type === 'pdf' ? 'file-text' : 'edit-3'}" style="width:14px;"></i> ${l.title}</div>`).join('')}
                        </div>
                    </div>`).join('')}
                </div>
            </div>
            
            <div class="card h-fit">
                <div class="card-header"><div class="flex justify-between items-center w-full"><span style="color:var(--primary); font-weight: 700;">Nội dung bài học</span><button class="btn btn-ghost btn-sm" onclick="renderPage('courses')">Trở lại khóa học</button></div></div>
                <div class="card-body" id="lesson-viewer" style="padding: 0;">
                    <p style="color:var(--text3); text-align:center; padding:80px 20px;">Hãy chọn một bài học ở danh sách bên trái để bắt đầu.</p>
                </div>
            </div>
        </div>`;
    lucide.createIcons();
}

function renderLessonInDetail(courseId, chapId, lessonId) {
    const course = COURSES_DATA.find(c => c.id === courseId);
    const chapter = course.chapters.find(c => c.id === chapId);
    const lesson = chapter.lessons.find(l => l.id === lessonId);
    const viewer = document.getElementById('lesson-viewer');
    
    let media = '';
    
    if (lesson.type === 'video') {
        media = `<div class="video-frame" style="height: 450px;"><iframe src="${lesson.url}" style="width:100%; height:100%; border:none;" allowfullscreen></iframe></div>`;
    } 
    else if (lesson.type === 'pdf') {
        // --- SỬA ĐỔI TẠI ĐÂY ĐỂ DÙNG IFRAME CHO GOOGLE DRIVE ---
        media = `
            <div class="pdf-container" style="display: flex; flex-direction: column; background: #fff;">
                <div style="padding: 12px 20px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 13px; font-weight: 600; color: #475569;"><i data-lucide="file-text" style="width: 14px; display: inline-block; vertical-align: middle;"></i> Tài liệu học tập (Google Drive)</span>
                    <a href="${lesson.url}" target="_blank" class="btn btn-primary btn-sm" style="text-decoration: none;"><i data-lucide="external-link" style="width: 14px;"></i> Mở trong tab mới</a>
                </div>
                <div style="height: 700px; width: 100%;">
                    <iframe src="${lesson.url}" width="100%" height="100%" style="border:none;" allow="autoplay"></iframe>
                </div>
            </div>`;
    } 
    else {
        media = `<div class="quiz-frame" style="padding: 30px;"><h2 style="font-size:18px; font-weight:700; margin-bottom:20px;">Trắc nghiệm: ${lesson.title}</h2><form id="quiz-form">${lesson.questions.map((q, i) => `<div style="margin-bottom:24px;"><p style="font-weight:600; margin-bottom:12px;">Câu ${i + 1}: ${q.q}</p><div>${q.a.map((ans, idx) => `<label class="quiz-opt" style="display: block; padding: 10px; border: 1px solid #eee; margin-bottom: 8px; border-radius: 8px; cursor: pointer;"><input type="radio" name="q${i}" value="${idx}" style="margin-right: 10px;"><span>${ans}</span></label>`).join('')}</div></div>`).join('')}<button type="button" class="btn btn-primary" style="width:100%; padding:12px;" onclick='submitQuiz(${JSON.stringify(course)}, ${JSON.stringify(lesson)})'>Xác nhận nộp bài</button></form></div>`;
    }
    
    viewer.innerHTML = media;
    lucide.createIcons();
}

function submitQuiz(course, lesson) {
    const form = document.getElementById('quiz-form');
    let correct = 0;
    lesson.questions.forEach((q, i) => {
        const checked = form.querySelector(`input[name="q${i}"]:checked`);
        if (checked && parseInt(checked.value) === q.c) correct++;
    });
    if (form.querySelectorAll('input:checked').length < lesson.questions.length) { alert("Vui lòng hoàn thành tất cả câu hỏi!"); return; }
    const finalScore = parseFloat(((correct / lesson.questions.length) * 10).toFixed(1));
    let db = getDB();
    db.scores.push({ name: lesson.title, score: finalScore, date: new Date().toLocaleDateString('vi-VN') });
    
    const assignment = ASSIGNMENTS_DATA.find(a => a.lessonId === lesson.id);
    if(assignment) assignment.status = 'Đã hoàn thành';
    
    setDB(db);
    alert(`Hoàn thành! Bạn đạt ${finalScore}/10 điểm.`);
    renderPage('assignments');
}

function closeJoinModal() {
    document.getElementById('join-course-code').value = '';
    document.getElementById('join-modal').classList.remove('active');
}

function joinCourse() {
    const codeInput = document.getElementById('join-course-code').value.trim().toUpperCase();
    if (!codeInput) { alert("Vui lòng nhập mã khóa học!"); return; }

    const courseFound = SYSTEM_DATABASE_COURSES.find(c => c.code === codeInput);
    if (!courseFound) { alert("Mã khóa học không tồn tại (Thử mã: ENG12)!"); return; }

    if (COURSES_DATA.some(c => c.id === courseFound.id)) { alert("Bạn đã tham gia khóa học này rồi!"); return; }

    COURSES_DATA.push(courseFound);
    const db = getDB();
    if(!db.joinedCourses) db.joinedCourses = [];
    db.joinedCourses.push(courseFound.id);
    setDB(db);

    alert(`Bạn đã được thêm vào lớp: ${courseFound.title}`);
    closeJoinModal();
    renderPage('courses');
}

function initChart() {
    const ctx = document.getElementById('myChart');
    if (!ctx) return;
    const db = getDB();
    const scores = db.scores.slice(-6).map(s => s.score);
    if (myChart) myChart.destroy();
    myChart = new Chart(ctx, {
        type: 'line',
        data: { labels: scores.map((_, i) => 'L' + (i+1)), datasets: [{ data: scores, borderColor: '#3b7dd8', tension: 0.4, fill: true, backgroundColor: 'rgba(59, 130, 246, 0.05)', borderWidth: 3 }] },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, max: 10 } } }
    });
}

function handleLogout() {
    const isConfirm = confirm("Bạn có chắc chắn muốn đăng xuất khỏi hệ thống?");
    
    if (isConfirm) {
        localStorage.removeItem('currentUser');
        window.location.href = 'login.html'; 
    }
}