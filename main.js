let myChart = null;
const getDB = () => JSON.parse(localStorage.getItem('portal_db')) || { scores: [], progress: {} };
const setDB = (db) => localStorage.setItem('portal_db', JSON.stringify(db));

const calculateGPA = () => {
    const db = getDB();
    if (db.scores.length === 0) return "0.00";
    return (db.scores.reduce((sum, s) => sum + s.score, 0) / db.scores.length).toFixed(2);
};

const calculateTotalProgress = () => {
    const db = getDB();
    let totalLessons = 0;
    COURSES_DATA.forEach(c => totalLessons += c.lessons.length);
    const completed = Object.keys(db.progress).length;
    return totalLessons > 0 ? Math.round((completed / totalLessons) * 100) : 0;
};

document.addEventListener('DOMContentLoaded', () => {
    lucide.createIcons();
    renderPage('dashboard');
    setupNotif();
    
    document.querySelectorAll('.nav-btn').forEach(btn => {
        btn.onclick = function() {
            document.querySelectorAll('.nav-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            renderPage(this.dataset.page);
        };
    });
});

// --- PHẦN CẬP NHẬT: THÔNG BÁO THỰC TẾ ---
function setupNotif() {
    const btn = document.getElementById('notif-btn');
    const panel = document.getElementById('notif-panel');
    const list = document.getElementById('notif-list');
    if(!btn) return;

    btn.onclick = (e) => { 
        e.stopPropagation(); 
        panel.classList.toggle('hidden'); 
        
        // Cập nhật nội dung thông báo mỗi khi mở panel
        const db = getDB();
        const pendingCount = [];
        COURSES_DATA.forEach(c => c.lessons.forEach(l => {
            if (l.type === 'quiz' && !db.scores.some(s => s.name === l.title)) pendingCount.push(l.title);
        }));

        let notifHTML = '';
        
        // Thông báo 1: Luôn có (Hệ thống)
        notifHTML += `<div class="p-4 border-b hover:bg-slate-50 cursor-pointer"><p class="text-[10px] font-bold text-blue-600 uppercase">Hệ thống</p><p class="text-[11px] text-slate-500 mt-1">Chào mừng bạn đã trở lại EdTech Hub!</p></div>`;

        // Thông báo 2: Nhắc nhở bài tập thực tế
        if (pendingCount.length > 0) {
            notifHTML += `
                <div class="p-4 border-b hover:bg-slate-50 cursor-pointer">
                    <p class="text-[10px] font-bold text-amber-600 uppercase">Nhắc nhở</p>
                    <p class="text-[11px] text-slate-500 mt-1">Bạn còn <b>${pendingCount.length}</b> bài tập chưa hoàn thành.</p>
                </div>`;
        }

        // Thông báo 3: Kết quả mới nhất
        if (db.scores.length > 0) {
            const lastScore = db.scores[db.scores.length - 1];
            notifHTML += `
                <div class="p-4 hover:bg-slate-50 cursor-pointer">
                    <p class="text-[10px] font-bold text-emerald-600 uppercase">Hoàn thành</p>
                    <p class="text-[11px] text-slate-500 mt-1">Bạn đã đạt <b>${lastScore.score}/10</b> điểm ở bài <i>${lastScore.name}</i>.</p>
                </div>`;
        }

        list.innerHTML = notifHTML;
    };

    document.onclick = () => panel.classList.add('hidden');
    panel.onclick = (e) => e.stopPropagation();
}

function renderPage(page) {
    const view = document.getElementById('main-view');
    const db = getDB();
    view.innerHTML = '';

    if (page === 'dashboard') {
        const gpa = calculateGPA();
        const prog = calculateTotalProgress();
        const pending = [];
        COURSES_DATA.forEach(c => c.lessons.forEach(l => {
            if (l.type === 'quiz' && !db.scores.some(s => s.name === l.title)) pending.push({ c, l });
        }));

        view.innerHTML = `
            <div class="animate-fade-in space-y-10">
                <div class="flex justify-between items-center">
                    <h1 class="text-3xl font-extrabold text-slate-800 uppercase tracking-tight">Dashboard</h1>
                    <button class="bg-blue-600 text-white px-7 py-3 rounded-2xl text-[11px] font-bold shadow-xl">HỖ TRỢ</button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                    <div class="stat-card">
                        <p class="text-[10px] font-bold text-blue-600 uppercase tracking-widest">GPA Thực tế</p>
                        <div class="text-4xl font-black mt-4">${gpa}</div>
                        <div class="mt-6 h-1 w-full bg-slate-100 rounded-full overflow-hidden">
                            <div class="bg-blue-600 h-full transition-all duration-700" style="width: ${parseFloat(gpa)*10}%"></div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <p class="text-[10px] font-bold text-indigo-600 uppercase tracking-widest">Tiến độ thật</p>
                        <div class="text-4xl font-black mt-4 text-slate-800">${prog}%</div>
                        <div class="w-full h-2.5 bg-slate-100 rounded-full mt-6 overflow-hidden">
                            <div class="h-full bg-blue-600 rounded-full transition-all duration-1000 ease-out" style="width: ${prog}%"></div>
                        </div>
                        <p class="text-[9px] mt-5 text-slate-400 font-bold italic">Đã xong ${Object.keys(db.progress).length} bài học</p>
                    </div>

                    <div class="lg:col-span-2 bg-white p-8 rounded-[2.5rem] border shadow-sm flex flex-col justify-center">
                        <h3 class="font-bold text-[10px] mb-6 text-slate-400 uppercase tracking-widest flex items-center gap-2"><i data-lucide="trending-up" class="w-4 h-4 text-blue-600"></i> Biểu đồ GPA</h3>
                        <div class="h-32 w-full"><canvas id="myChart"></canvas></div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">
                    <div class="bg-white p-10 rounded-[3rem] border shadow-sm">
                        <h3 class="font-extrabold text-slate-800 mb-8 flex items-center gap-3 italic"><i data-lucide="calendar" class="text-blue-600"></i> Lịch học</h3>
                        <div class="space-y-4">
                            ${SCHEDULE_DATA.map(s => `<div class="flex items-center gap-5 p-5 bg-emerald-50 text-emerald-700 rounded-3xl border border-emerald-100"><i data-lucide="clock" class="w-5 h-5"></i><div class="text-sm font-bold">${s.time} - ${s.subject}</div></div>`).join('')}
                        </div>
                    </div>
                    <div class="bg-white p-10 rounded-[3rem] border shadow-sm">
                        <h3 class="font-extrabold text-slate-800 mb-8 flex items-center gap-3 italic"><i data-lucide="list-checks" class="text-blue-600"></i> Nhiệm vụ</h3>
                        <div class="space-y-4">
                            ${pending.length === 0 ? `<p class="text-center text-slate-400 italic py-10 font-bold">Hết bài tập! 🎉</p>` : 
                            pending.map(q => `
                                <div class="flex justify-between items-center p-5 bg-slate-50 rounded-3xl border border-transparent hover:border-blue-200 transition-all group">
                                    <p class="text-sm font-bold text-slate-700">${q.l.title}</p>
                                    <button onclick="openLessonById(${q.c.id}, ${q.l.id})" class="text-[10px] font-black text-white bg-blue-600 px-5 py-2 rounded-xl opacity-0 group-hover:opacity-100 transition-all shadow-lg shadow-blue-200 uppercase">Làm ngay</button>
                                </div>`).join('')}
                        </div>
                    </div>
                </div>
            </div>`;
        setTimeout(initChart, 100);
    } else if (page === 'courses') {
        view.innerHTML = `<h2 class="text-3xl font-extrabold italic mb-10 text-slate-800 uppercase tracking-tight">Khóa học</h2><div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">${COURSES_DATA.map(c => `<div class="bg-white p-10 rounded-[2.5rem] border shadow-sm transition hover:shadow-xl hover:-translate-y-2"><h4 class="font-extrabold text-xl mb-8">${c.title}</h4><button onclick="openCourse(${c.id})" class="text-blue-600 font-black text-xs flex items-center gap-3 hover:gap-5 transition-all uppercase tracking-widest">Vào học ngay <i data-lucide="arrow-right"></i></button></div>`).join('')}</div>`;
    } else if (page === 'progress') {
        view.innerHTML = `<h2 class="text-3xl font-extrabold italic mb-10 text-slate-800 uppercase tracking-tight">Lịch sử</h2><div class="bg-white rounded-[2.5rem] border overflow-hidden shadow-sm"><table class="w-full text-left text-sm"><thead class="bg-slate-50 border-b text-[10px] font-bold uppercase text-slate-400"><tr><th class="p-6">Bài tập</th><th class="p-6 text-center">Điểm</th><th class="p-6 text-right">Ngày</th></tr></thead><tbody class="divide-y text-slate-700">${db.scores.length === 0 ? `<tr><td colspan="3" class="p-16 text-center italic text-slate-400">Chưa có kết quả.</td></tr>` : db.scores.map(s => `<tr><td class="p-6 font-bold text-slate-800">${s.name}</td><td class="p-6 text-center"><span class="bg-blue-50 text-blue-600 px-4 py-1.5 rounded-xl font-black border border-blue-100">${s.score}</span></td><td class="p-6 text-right text-slate-400 font-bold">${s.date}</td></tr>`).reverse().join('')}</tbody></table></div>`;
    }
    lucide.createIcons();
}

function openLessonById(cId, lId) {
    const course = COURSES_DATA.find(c => c.id === cId);
    const lesson = course.lessons.find(l => l.id === lId);
    renderLesson(course, lesson);
}

function openCourse(id) {
    const course = COURSES_DATA.find(c => c.id === id);
    renderLesson(course, course.lessons[0]);
}

function renderLesson(course, lesson) {
    const view = document.getElementById('main-view');
    view.innerHTML = `
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-10 animate-fade-in">
            <div class="lg:col-span-2 space-y-8">
                <button onclick="renderPage('dashboard')" class="text-[11px] font-black text-slate-400 hover:text-blue-600 uppercase tracking-widest flex items-center gap-2 transition-all hover:gap-4">&larr; Quay lại</button>
                <div id="content-area" class="relative aspect-video bg-black rounded-[2.5rem] overflow-hidden border-4 border-white shadow-2xl group">
                    ${lesson.type === 'video' ? `<iframe class="w-full h-full" src="${lesson.url}" frameborder="0" allowfullscreen></iframe>` : renderQuizUI(course, lesson)}
                    ${lesson.type === 'video' ? `<button onclick="alert('Đang tải tài liệu PDF cho: ${lesson.title}')" class="absolute top-6 right-6 flex items-center gap-2 bg-white/20 backdrop-blur-md text-white px-5 py-2.5 rounded-2xl text-[11px] font-bold border border-white/30 hover:bg-white/40 transition-all opacity-0 group-hover:opacity-100"><i data-lucide="download-cloud"></i> Tải Tài Liệu PDF</button>` : ''}
                </div>
                <h1 class="text-3xl font-black text-slate-800 tracking-tight">${lesson.title}</h1>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="bg-white p-8 rounded-[2.5rem] border shadow-sm"><h4 class="font-bold text-xs mb-5 uppercase tracking-widest text-slate-400 italic">Ghi chú</h4><textarea id="lesson-note" class="w-full h-32 bg-slate-50 p-5 rounded-3xl text-[11px] outline-none" placeholder="Viết note..."></textarea></div>
                    <div class="bg-white p-8 rounded-[2.5rem] border shadow-sm"><h4 class="font-bold text-xs mb-5 uppercase tracking-widest text-slate-400 italic">Thảo luận</h4><div class="h-32 overflow-y-auto mb-4 bg-slate-50 p-4 rounded-3xl text-[10px]"><b>Hệ thống:</b> Hãy đặt câu hỏi nếu bạn cần trợ giúp bài học này.</div><input type="text" class="w-full bg-slate-50 p-3 px-5 rounded-full text-[10px]" placeholder="Câu hỏi..."></div>
                </div>
            </div>
            <div class="bg-white rounded-[3rem] border p-8 h-fit shadow-sm"><h4 class="font-black text-[11px] text-slate-400 mb-10 px-4 uppercase tracking-widest italic">Nội dung học</h4>
                ${course.lessons.map(l => `<button onclick='renderLesson(${JSON.stringify(course)}, ${JSON.stringify(l)})' class="w-full flex items-center gap-5 p-5 rounded-3xl transition-all text-[13px] ${l.id === lesson.id ? 'bg-blue-50 text-blue-700 font-extrabold shadow-sm' : 'text-slate-500 hover:bg-slate-50'}"><i data-lucide="${l.type === 'video' ? 'play-circle' : 'help-circle'}" class="w-5 h-5"></i><span class="text-left">${l.title}</span></button>`).join('')}
            </div>
        </div>`;
    lucide.createIcons();
}

function renderQuizUI(course, lesson) {
    return `<div class="bg-white p-12 rounded-[2.5rem] shadow-sm border animate-fade-in"><form id="quiz-form" class="space-y-10">${lesson.questions.map((q, i) => `<div class="quiz-question-block"><p class="font-black text-slate-800 mb-8 text-lg italic">${i + 1}. ${q.q}</p><div class="grid gap-4">${q.a.map((ans, idx) => `<label class="quiz-opt group"><input type="radio" name="q${i}" value="${idx}"><span class="block w-full p-4 border border-slate-100 rounded-2xl cursor-pointer transition-all group-hover:border-blue-200">${ans}</span></label>`).join('')}</div></div>`).join('')}<button type="button" onclick='submitQuiz(${JSON.stringify(course)}, ${JSON.stringify(lesson)})' class="w-full bg-blue-600 text-white font-black py-5 rounded-[2rem] hover:bg-blue-700 transition shadow-2xl active:scale-[0.98] uppercase tracking-widest text-xs">XÁC NHẬN NỘP BÀI</button></form></div>`;
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
    db.progress[`${course.id}_${lesson.id}`] = 100;
    setDB(db);
    alert(`Hoàn thành! Bạn đạt ${finalScore}/10. Nhiệm vụ đã biến mất!`);
    renderPage('dashboard');
}

function initChart() {
    const ctx = document.getElementById('myChart');
    if (!ctx) return;
    const db = getDB();
    const scores = db.scores.slice(-6).map(s => s.score);
    if (myChart) myChart.destroy();
    myChart = new Chart(ctx, {
        type: 'line',
        data: { labels: scores.map((_, i) => 'Lần ' + (i+1)), datasets: [{ data: scores, borderColor: '#3b82f6', tension: 0.4, fill: true, backgroundColor: 'rgba(59, 130, 246, 0.05)', borderWidth: 3, pointRadius: 4, pointBackgroundColor: '#fff' }] },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, max: 10 } } }
    });
}