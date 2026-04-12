// ==========================================
// 1. KHÓA HỌC HIỆN CÓ CỦA HỌC SINH
// ==========================================
const COURSES_DATA = [
    {
        id: 1, title: "Toán Đại Lớp 11", teacher: "Thầy Tâm", progress: 25,
        chapters: [
            {
                id: 'c1', title: "HÀM SỐ MŨ VÀ LÔGARIT", progress: "1/4 bài đã học",
                lessons: [
                    { 
                        id: 100, 
                        title: "Sách giáo khoa Toán 11 (Mở trang 15)", 
                        type: "pdf", 
                        // Link này là file test có sẵn trên mạng. 
                        // Chú ý: Nếu dùng file thật ở máy bạn, đổi thành: "./ten-file.pdf"
                        url: "https://drive.google.com/file/d/1vtYCX9C32jOndyrOW9Gi6fw3KKnBitR6/view?usp=sharing" 
                    },
                    { 
                        id: 101, 
                        title: "Mục 1: Đề trên lớp (Bản PDF)", 
                        type: "pdf",
                        url: "https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf" 
                    },
                    { id: 102, title: "Mục 2: Video bài giảng", type: "video", url: "https://www.youtube.com/embed/l1sehS90CIs" },
                    { id: 103, title: "BTVN Buổi 50", type: "quiz", questions: [{q: "1+1=?", a:["2","3"], c:0}] }
                ]
            }
        ]
    }
];

// ==========================================
// 2. BÀI TẬP HIỂN THỊ Ở TAB "BÀI TẬP CỦA TÔI"
// ==========================================
const ASSIGNMENTS_DATA = [
    { id: 1, courseId: 1, lessonId: 103, title: "BTVN - Buổi 50. Hàm số mũ", duration: "120 phút", deadline: "12/04/2026", status: "Chưa làm" },
    { id: 2, courseId: 1, lessonId: 103, title: "BTVN - Buổi 19. Đạo hàm", duration: "90 phút", deadline: "03/04/2026", status: "Đã hoàn thành" }
];

// ==========================================
// 3. DATABASE KHÓA HỌC ĐỂ NHẬP MÃ THAM GIA
// ==========================================
const SYSTEM_DATABASE_COURSES = [
    {
        id: 99, code: "ENG12", title: "Tiếng Anh IELTS 6.5", teacher: "Cô Lan", progress: 0,
        chapters: [
            {
                id: 'e1', title: "Listening Part 1", progress: "0/1 bài đã học",
                lessons: [{ id: 991, title: "Chiến thuật nghe", type: "video", url: "https://www.youtube.com/embed/dQw4w9WgXcQ" }]
            }
        ]
    }
];

// ==========================================
// 4. LỊCH HỌC TẬP (BÊN PHẢI DASHBOARD)
// ==========================================
const SCHEDULE_DATA = [
    { time: "08:00 - 09:30", subject: "Lập trình ReactJS", room: "Phòng 301" },
    { time: "14:00 - 15:30", subject: "Thiết kế UI/UX", room: "Online Zoom" }
];