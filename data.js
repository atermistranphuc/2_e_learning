const COURSES_DATA = [
    {
        id: 1, title: "Lập trình ReactJS Pro",
        lessons: [
            { id: 101, title: "1. Giới thiệu React Hooks", type: "video", url: "https://www.youtube.com/watch?v=l1sehS90CIs" },
            { id: 102, title: "Trắc nghiệm kiến thức Hook", type: "quiz", questions: [{ q: "useState dùng để làm gì?", a: ["Quản lý trạng thái", "Tạo vòng lặp"], c: 0 }] }
        ]
    },
    {
        id: 2, title: "UI/UX Design Master",
        lessons: [
            { id: 201, title: "1. Nguyên tắc Grid System", type: "video", url: "https://www.youtube.com/embed/dQw4w9WgXcQ" },
            { id: 202, title: "Quiz về Màu sắc UI", type: "quiz", questions: [{ q: "Màu nào là màu tương phản của xanh?", a: ["Cam", "Tím"], c: 0 }] }
        ]
    }
];

const SCHEDULE_DATA = [
    { time: "08:00", subject: "Lập trình ReactJS", room: "Phòng 301" },
    { time: "14:00", subject: "Thiết kế UI/UX", room: "Online Zoom" }
];