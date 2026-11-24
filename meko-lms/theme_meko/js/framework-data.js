    const dataVstep = {
      id: "vstep",
      idnumber: "MEKO_VSTEP",
      name: "Khung Năng Lực Ngoại Ngữ 6 Bậc Dùng Cho Việt Nam (VSTEP)",
      authority: "Ban hành theo Thông tư 01/2014/TT-BGDĐT",
      csvPath: "../data/khung_nang_luc_vstep_6bac_vn.csv",
      description: "Khung tham chiếu năng lực ngoại ngữ 6 bậc dùng cho Việt Nam, chuẩn hóa mục tiêu học tập và đánh giá kiểm tra chuẩn đầu ra ngoại ngữ cho sinh viên, giảng viên và nhân sự chất lượng cao.",
      levels: [
        { code: "A1", name: "Bậc 1 (Sơ cấp A1)", desc: "Hiểu và sử dụng cấu trúc quen thuộc hàng ngày, chào hỏi, tự giới thiệu đơn giản." },
        { code: "A2", name: "Bậc 2 (Sơ cấp A2)", desc: "Giao tiếp trong các tình huống đơn giản về thông tin cá nhân, gia đình, mua sắm." },
        { code: "B1", name: "Bậc 3 (Trung cấp B1)", desc: "Hiểu ý chính chủ đề quen thuộc (học tập, làm việc). Chuẩn tốt nghiệp Đại học." },
        { code: "B2", name: "Bậc 4 (Trung cấp B2)", desc: "Nắm nội dung văn bản phức tạp, trao đổi trôi chảy tự nhiên với người bản ngữ." },
        { code: "C1", name: "Bậc 5 (Cao cấp C1)", desc: "Hiểu rộng các văn bản dài, nhận biết ẩn ý. Sử dụng ngôn ngữ linh hoạt cho học thuật." },
        { code: "C2", name: "Bậc 6 (Cao cấp C2)", desc: "Sử dụng ngôn ngữ tinh tế, chính xác tuyệt đối như người bản ngữ có học thức cao." }
      ],
      domains: [
        {
          code: "VSTEP_READ",
          name: "Kỹ năng Đọc hiểu (Reading Comprehension)",
          en_name: "Reading Skill",
          description: "Năng lực tiếp nhận, phân tích, suy luận và tổng hợp thông tin từ văn bản tiếng Anh theo các bậc trình độ.",
          competencies: [
            { code: "VSTEP_READ_A1", level: "A1", name: "Đọc hiểu Bậc 1 (A1 - Sơ cấp)", statement: "Hiểu được các từ ngữ quen thuộc và câu rất đơn giản trên biển báo, áp phích hoặc catalog giới thiệu.", rule: "Điểm Quiz >= 70% đạt chuẩn", course: "ENG-101: Tiếng Anh Khởi Động" },
            { code: "VSTEP_READ_A2", level: "A2", name: "Đọc hiểu Bậc 2 (A2 - Sơ cấp)", statement: "Đọc hiểu các thông báo ngắn, tờ rơi, thực đơn và lịch trình hàng ngày quen thuộc.", rule: "Điểm Quiz >= 70% đạt chuẩn", course: "ENG-102: Tiếng Anh Giao Tiếp Cơ Bản" },
            { code: "VSTEP_READ_B1", level: "B1", name: "Đọc hiểu Bậc 3 (B1 - Trung cấp)", statement: "Đọc hiểu các văn bản chứa ngôn ngữ thực tế liên quan đến công việc hoặc học tập chuyên ngành.", rule: "Điểm Quiz >= 70% đạt chuẩn", course: "ENG-201: Tiếng Anh Chuẩn Đầu Ra B1" },
            { code: "VSTEP_READ_B2", level: "B2", name: "Đọc hiểu Bậc 4 (B2 - Trung cấp)", statement: "Đọc hiểu các bài báo, báo cáo chuyên môn đương đại và phân tích quan điểm tác giả.", rule: "Điểm Quiz >= 70% đạt chuẩn", course: "ENG-301: Tiếng Anh Học Thuật B2" },
            { code: "VSTEP_READ_C1", level: "C1", name: "Đọc hiểu Bậc 5 (C1 - Cao cấp)", statement: "Đọc hiểu và đánh giá phản biện các tài liệu học thuật phức tạp, tiểu luận chuyên sâu.", rule: "Điểm Quiz >= 70% đạt chuẩn", course: "ENG-401: Tiếng Anh Nghiên Cứu C1" },
            { code: "VSTEP_READ_C2", level: "C2", name: "Đọc hiểu Bậc 6 (C2 - Cao cấp)", statement: "Đọc hiểu hầu như mọi thể loại văn bản học thuật và văn học cổ điển một cách dễ dàng.", rule: "Điểm Quiz >= 70% đạt chuẩn", course: "ENG-501: Tiếng Anh Bậc Thầy C2" }
          ]
        },
        {
          code: "VSTEP_LIST",
          name: "Kỹ năng Nghe hiểu (Listening Comprehension)",
          en_name: "Listening Skill",
          description: "Năng lực tiếp nhận thông điệp âm thanh, phân biệt ngữ điệu và nắm bắt chi tiết thông tin từ hội thoại và bài giảng.",
          competencies: [
            { code: "VSTEP_LIST_A1", level: "A1", name: "Nghe hiểu Bậc 1 (A1 - Sơ cấp)", statement: "Nhận biết các từ quen thuộc và cụm từ cơ bản khi người đối thoại nói chậm rãi và rõ ràng.", rule: "Điểm Quiz >= 70% đạt chuẩn", course: "ENG-101: Tiếng Anh Khởi Động" },
            { code: "VSTEP_LIST_A2", level: "A2", name: "Nghe hiểu Bậc 2 (A2 - Sơ cấp)", statement: "Nắm được ý chính trong các thông báo và thông điệp ngắn đơn giản hàng ngày.", rule: "Điểm Quiz >= 70% đạt chuẩn", course: "ENG-102: Tiếng Anh Giao Tiếp Cơ Bản" },
            { code: "VSTEP_LIST_B1", level: "B1", name: "Nghe hiểu Bậc 3 (B1 - Trung cấp)", statement: "Hiểu các ý chính của bài nói rõ ràng về các vấn đề quen thuộc trong công việc và học tập.", rule: "Điểm Quiz >= 70% đạt chuẩn", course: "ENG-201: Tiếng Anh Chuẩn Đầu Ra B1" },
            { code: "VSTEP_LIST_B2", level: "B2", name: "Nghe hiểu Bậc 4 (B2 - Trung cấp)", statement: "Hiểu các bài thuyết trình dài, thảo luận kỹ thuật trong lĩnh vực chuyên ngành quen thuộc.", rule: "Điểm Quiz >= 70% đạt chuẩn", course: "ENG-301: Tiếng Anh Học Thuật B2" },
            { code: "VSTEP_LIST_C1", level: "C1", name: "Nghe hiểu Bậc 5 (C1 - Cao cấp)", statement: "Hiểu hầu hết các chương trình truyền thanh, phim ảnh và hội thảo khoa học không bị rào cản ngữ điệu.", rule: "Điểm Quiz >= 70% đạt chuẩn", course: "ENG-401: Tiếng Anh Nghiên Cứu C1" },
            { code: "VSTEP_LIST_C2", level: "C2", name: "Nghe hiểu Bậc 6 (C2 - Cao cấp)", statement: "Nghe hiểu mọi dạng ngôn ngữ nói trực tiếp hay truyền thông ở tốc độ bản ngữ tự nhiên.", rule: "Điểm Quiz >= 70% đạt chuẩn", course: "ENG-501: Tiếng Anh Bậc Thầy C2" }
          ]
        },
        {
          code: "VSTEP_WRIT",
          name: "Kỹ năng Viết (Writing Competence)",
          en_name: "Writing Skill",
          description: "Khả năng diễn đạt bằng văn bản từ thư tín cá nhân, email công vụ đến báo cáo học thuật chuyên sâu.",
          competencies: [
            { code: "VSTEP_WRIT_A1", level: "A1", name: "Viết Bậc 1 (A1 - Sơ cấp)", statement: "Viết thiệp ngắn, điền mẫu biểu cá nhân (tên, quốc tịch, địa chỉ) đơn giản.", rule: "Điểm Quiz >= 70% đạt chuẩn", course: "ENG-101: Tiếng Anh Khởi Động" },
            { code: "VSTEP_WRIT_A2", level: "A2", name: "Viết Bậc 2 (A2 - Sơ cấp)", statement: "Viết mẩu tin nhắn ngắn, ghi chú đơn giản và thư cảm ơn ngắn gọn.", rule: "Điểm Quiz >= 70% đạt chuẩn", course: "ENG-102: Tiếng Anh Giao Tiếp Cơ Bản" },
            { code: "VSTEP_WRIT_B1", level: "B1", name: "Viết Bậc 3 (B1 - Trung cấp)", statement: "Viết đoạn văn liên kết về chủ đề quen thuộc, thư tín cá nhân thuật lại trải nghiệm.", rule: "Điểm Quiz >= 70% đạt chuẩn", course: "ENG-201: Tiếng Anh Chuẩn Đầu Ra B1" },
            { code: "VSTEP_WRIT_B2", level: "B2", name: "Viết Bậc 4 (B2 - Trung cấp)", statement: "Viết bài luận rõ ràng, chi tiết, tổng hợp thông tin và đưa ra quan điểm ủng hộ/phản đối.", rule: "Điểm Quiz >= 70% đạt chuẩn", course: "ENG-301: Tiếng Anh Học Thuật B2" },
            { code: "VSTEP_WRIT_C1", level: "C1", name: "Viết Bậc 5 (C1 - Cao cấp)", statement: "Soạn thảo bài tiểu luận, báo cáo hoặc thư từ chuyên nghiệp có bố cục chặt chẽ, mạch lạc.", rule: "Điểm Quiz >= 70% đạt chuẩn", course: "ENG-401: Tiếng Anh Nghiên Cứu C1" },
            { code: "VSTEP_WRIT_C2", level: "C2", name: "Viết Bậc 6 (C2 - Cao cấp)", statement: "Viết tài liệu học thuật phức tạp hoặc văn bản pháp lý với phong cách văn phong chuẩn xác, sắc bén.", rule: "Điểm Quiz >= 70% đạt chuẩn", course: "ENG-501: Tiếng Anh Bậc Thầy C2" }
          ]
        },
        {
          code: "VSTEP_SPK",
          name: "Kỹ năng Nói (Speaking Competence)",
          en_name: "Speaking Skill",
          description: "Khả năng tương tác trực tiếp, trình bày ý kiến, diễn thuyết và đàm phán trong môi trường đa văn hóa.",
          competencies: [
            { code: "VSTEP_SPK_A1", level: "A1", name: "Nói Bậc 1 (A1 - Sơ cấp)", statement: "Giao tiếp đơn giản nếu người đối thoại sẵn sàng nhắc lại hoặc nói chậm lại.", rule: "Điểm Quiz >= 70% đạt chuẩn", course: "ENG-101: Tiếng Anh Khởi Động" },
            { code: "VSTEP_SPK_A2", level: "A2", name: "Nói Bậc 2 (A2 - Sơ cấp)", statement: "Giao tiếp trong các tình huống thường nhật đòi hỏi trao đổi thông tin trực tiếp, đơn giản.", rule: "Điểm Quiz >= 70% đạt chuẩn", course: "ENG-102: Tiếng Anh Giao Tiếp Cơ Bản" },
            { code: "VSTEP_SPK_B1", level: "B1", name: "Nói Bậc 3 (B1 - Trung cấp)", statement: "Xử lý hầu hết các tình huống nảy sinh khi đi lại, trình bày lý do và kế hoạch ngắn gọn.", rule: "Điểm Quiz >= 70% đạt chuẩn", course: "ENG-201: Tiếng Anh Chuẩn Đầu Ra B1" },
            { code: "VSTEP_SPK_B2", level: "B2", name: "Nói Bậc 4 (B2 - Trung cấp)", statement: "Giao tiếp tự tin, bảo vệ quan điểm trong tranh luận và thuyết trình dự án rõ ràng.", rule: "Điểm Quiz >= 70% đạt chuẩn", course: "ENG-301: Tiếng Anh Học Thuật B2" },
            { code: "VSTEP_SPK_C1", level: "C1", name: "Nói Bậc 5 (C1 - Cao cấp)", statement: "Sử dụng ngôn ngữ linh hoạt cho các mục đích xã hội và nghề nghiệp mà không gặp khó khăn.", rule: "Điểm Quiz >= 70% đạt chuẩn", course: "ENG-401: Tiếng Anh Nghiên Cứu C1" },
            { code: "VSTEP_SPK_C2", level: "C2", name: "Nói Bậc 6 (C2 - Cao cấp)", statement: "Thuyết trình và phản biện các đề tài khoa học trừu tượng một cách lưu loát và hoàn hảo.", rule: "Điểm Quiz >= 70% đạt chuẩn", course: "ENG-501: Tiếng Anh Bậc Thầy C2" }
          ]
        }
      ]
    };

    // 2. DATA KHUNG DIGCOMPEDU (CHÂU ÂU - 6 CẤP ĐỘ SƯ PHẠM SỐ)
    const dataDigCompEdu = {
      id: "digcompedu",
      idnumber: "MEKO_DIGCOMPEDU",
      name: "Khung Năng Lực Số Sư Phạm Châu Âu (DigCompEdu - 6 Cấp Độ)",
      authority: "European Framework for the Digital Competence of Educators",
      csvPath: "../data/khung_nang_luc_digcompedu_6bac_eu.csv",
      description: "Mô hình chuẩn hóa năng lực ứng dụng công nghệ số dành cho giảng viên và cán bộ quản lý đào tạo, gồm 6 lĩnh vực sư phạm số phân cấp từ cấp Khởi nhập (A1) đến Tiên phong (C2).",
      levels: [
        { code: "A1", name: "Newcomer (A1 - Khởi nhập)", desc: "Nhận biết tiềm năng của công nghệ số, sử dụng ở mức độ cơ bản dưới sự trợ giúp." },
        { code: "A2", name: "Explorer (A2 - Khám phá)", desc: "Bắt đầu thử nghiệm các công cụ số trong soạn giảng và tương tác người học." },
        { code: "B1", name: "Integrator (B1 - Tích hợp)", desc: "Tích hợp thường xuyên các ứng dụng số vào giảng dạy và quản lý lớp học." },
        { code: "B2", name: "Expert (B2 - Thành thạo)", desc: "Làm chủ công nghệ số, sử dụng sáng tạo và cố vấn hỗ trợ đồng nghiệp." },
        { code: "C1", name: "Leader (C1 - Dẫn dắt)", desc: "Dẫn dắt các sáng kiến đổi mới phương pháp sư phạm số trong khoa và trường." },
        { code: "C2", name: "Pioneer (C2 - Tiên phong)", desc: "Định hình các chuẩn mực sư phạm số mới, nghiên cứu và phát triển giải pháp đột phá." }
      ],
      domains: [
        {
          code: "DIG_AREA1",
          name: "Lĩnh vực 1: Gắn kết chuyên môn (Professional Engagement)",
          en_name: "Area 1: Professional Engagement",
          description: "Sử dụng công nghệ số trong giao tiếp tổ chức, cộng tác sư phạm và phát triển chuyên môn liên tục.",
          competencies: [
            { code: "DIG_A1_1", level: "A1", name: "A1.1: Nhận thức truyền thông số tổ chức", statement: "Nhận thức tiềm năng của email và LMS trong truyền thông sư phạm và trao đổi công vụ.", rule: "Điểm Quiz >= 70% đạt chuẩn", course: "EDU-101: Nhập Môn Sư Phạm Số" },
            { code: "DIG_A1_2", level: "A2", name: "A1.2: Sử dụng công cụ số trao đổi cơ bản", statement: "Thử nghiệm sử dụng email và nhóm chat trực tuyến để gửi tài liệu cho sinh viên.", rule: "Điểm Quiz >= 70% đạt chuẩn", course: "EDU-101: Nhập Môn Sư Phạm Số" },
            { code: "DIG_A1_3", level: "B1", name: "A1.3: Tích hợp kênh số quản lý lớp học", statement: "Tích hợp thường xuyên diễn đàn Moodle và thông báo tự động vào quản lý lớp học.", rule: "Điểm Quiz >= 70% đạt chuẩn", course: "EDU-201: Ứng Dụng Moodle Trong Dạy Học" },
            { code: "DIG_A1_4", level: "B2", name: "A1.4: Điều phối hợp tác chuyên môn số", statement: "Sử dụng linh hoạt các nền tảng đám mây chia sẻ tài nguyên và đồng soạn thảo bài giảng.", rule: "Điểm Quiz >= 70% đạt chuẩn", course: "EDU-301: Thiết Kế Khóa Học e-Learning Chuẩn" },
            { code: "DIG_A1_5", level: "C1", name: "A1.5: Dẫn dắt chuyển đổi số bộ môn", statement: "Dẫn dắt các sáng kiến chuyển đổi số phương pháp giảng dạy tại khoa và viện đào tạo.", rule: "Điểm Quiz >= 70% đạt chuẩn", course: "EDU-401: Lãnh Đạo Chuyển Đổi Số Giáo Dục" },
            { code: "DIG_A1_6", level: "C2", name: "A1.6: Tiên phong mô hình kết nối học thuật", statement: "Đổi mới sáng tạo mạng lưới trao đổi học thuật số liên trường tầm vóc quốc tế.", rule: "Điểm Quiz >= 70% đạt chuẩn", course: "EDU-501: Chuyên Gia Sư Phạm Số MEKO" }
          ]
        },
        {
          code: "DIG_AREA2",
          name: "Lĩnh vực 2: Tài nguyên học liệu số (Digital Resources)",
          en_name: "Area 2: Digital Resources",
          description: "Tìm kiếm, chọn lọc, tạo mới, chỉnh sửa và bảo vệ bản quyền tài nguyên giáo dục mở (OER).",
          competencies: [
            { code: "DIG_A2_1", level: "A1", name: "A2.1: Nhận diện nguồn học liệu số", statement: "Nhận diện các trang web chia sẻ học liệu và biết cách tải slide tài liệu bài giảng.", rule: "Điểm Quiz >= 70% đạt chuẩn", course: "EDU-101: Nhập Môn Sư Phạm Số" },
            { code: "DIG_A2_2", level: "A2", name: "A2.2: Chọn lọc tài liệu số theo bài giảng", statement: "Tìm kiếm và áp dụng các tiêu chí cơ bản để chọn tài liệu số phù hợp nội dung môn học.", rule: "Điểm Quiz >= 70% đạt chuẩn", course: "EDU-101: Nhập Môn Sư Phạm Số" },
            { code: "DIG_A2_3", level: "B1", name: "A2.3: Tái tạo và biên tập bài giảng số", statement: "Chỉnh sửa, kết hợp các nguồn tài nguyên số và đính kèm vào các ô Tiles trên Moodle.", rule: "Điểm Quiz >= 70% đạt chuẩn", course: "EDU-201: Ứng Dụng Moodle Trong Dạy Học" },
            { code: "DIG_A2_4", level: "B2", name: "A2.4: Xuất bản gói học liệu tương tác SCORM", statement: "Tạo gói SCORM/H5P tương tác cao và cấp phép bản quyền Creative Commons rõ ràng.", rule: "Điểm Quiz >= 70% đạt chuẩn", course: "EDU-301: Thiết Kế Khóa Học e-Learning Chuẩn" },
            { code: "DIG_A2_5", level: "C1", name: "A2.5: Xây dựng kho học liệu số cấp trường", statement: "Chủ trì xây dựng ngân hàng học liệu số mở dùng chung cho các phân hiệu và ngành học.", rule: "Điểm Quiz >= 70% đạt chuẩn", course: "EDU-401: Lãnh Đạo Chuyển Đổi Số Giáo Dục" },
            { code: "DIG_A2_6", level: "C2", name: "A2.6: Khởi xướng chiến lược học liệu mở", statement: "Tham mưu chính sách bản quyền và phát triển kho học liệu mở quy chuẩn quốc tế.", rule: "Điểm Quiz >= 70% đạt chuẩn", course: "EDU-501: Chuyên Gia Sư Phạm Số MEKO" }
          ]
        },
        {
          code: "DIG_AREA3",
          name: "Lĩnh vực 3: Dạy và học số (Teaching & Learning)",
          en_name: "Area 3: Teaching & Learning",
          description: "Thiết kế, triển khai và quản lý hoạt động dạy học trực tuyến và kết hợp (Blended Learning).",
          competencies: [
            { code: "DIG_A3_1", level: "A1", name: "A3.1: Dạy học với máy chiếu / slide cơ bản", statement: "Sử dụng thiết bị số trình chiếu bài giảng và hướng dẫn sinh viên xem tài liệu.", rule: "Điểm Quiz >= 70% đạt chuẩn", course: "EDU-101: Nhập Môn Sư Phạm Số" },
            { code: "DIG_A3_2", level: "A2", name: "A3.2: Thử nghiệm bài giảng video / podcast", statement: "Thử nghiệm thu âm hoặc quay video bài giảng ngắn hỗ trợ sinh viên tự học ở nhà.", rule: "Điểm Quiz >= 70% đạt chuẩn", course: "EDU-101: Nhập Môn Sư Phạm Số" },
            { code: "DIG_A3_3", level: "B1", name: "A3.3: Vận hành lớp học Blended Learning", statement: "Kết hợp linh hoạt giữa giảng dạy trực tiếp và giao bài tập tương tác trên Moodle Tiles.", rule: "Điểm Quiz >= 70% đạt chuẩn", course: "EDU-201: Ứng Dụng Moodle Trong Dạy Học" },
            { code: "DIG_A3_4", level: "B2", name: "A3.4: Cá nhân hóa lộ trình học tập số", statement: "Thiết lập điều kiện truy cập (Restrict Access) và lộ trình học tập phân nhánh cho sinh viên.", rule: "Điểm Quiz >= 70% đạt chuẩn", course: "EDU-301: Thiết Kế Khóa Học e-Learning Chuẩn" },
            { code: "DIG_A3_5", level: "C1", name: "A3.5: Cố vấn phương pháp dạy học kết hợp", statement: "Tập huấn và cố vấn phương pháp dạy học số tích cực cho đội ngũ giảng viên mới.", rule: "Điểm Quiz >= 70% đạt chuẩn", course: "EDU-401: Lãnh Đạo Chuyển Đổi Số Giáo Dục" },
            { code: "DIG_A3_6", level: "C2", name: "A3.6: Đổi mới sáng tạo sư phạm tương lai", statement: "Nghiên cứu ứng dụng công nghệ thực tế ảo (VR/AR) và AI Adaptive Learning vào giảng dạy.", rule: "Điểm Quiz >= 70% đạt chuẩn", course: "EDU-501: Chuyên Gia Sư Phạm Số MEKO" }
          ]
        },
        {
          code: "DIG_AREA4",
          name: "Lĩnh vực 4: Đánh giá học tập (Assessment)",
          en_name: "Area 4: Digital Assessment",
          description: "Sử dụng công nghệ số để kiểm tra đánh giá quá trình (formative) và định kỳ (summative).",
          competencies: [
            { code: "DIG_A4_1", level: "A1", name: "A4.1: Nhận bài tập qua hệ thống số", statement: "Cấu hình tiếp nhận bài tập sinh viên nộp dưới dạng file Word/PDF trên LMS.", rule: "Điểm Quiz >= 70% đạt chuẩn", course: "EDU-101: Nhập Môn Sư Phạm Số" },
            { code: "DIG_A4_2", level: "A2", name: "A4.2: Tạo bài trắc nghiệm Quiz cơ bản", statement: "Tạo bài trắc nghiệm khách quan với ngân hàng câu hỏi định dạng Aiken đơn giản.", rule: "Điểm Quiz >= 70% đạt chuẩn", course: "EDU-101: Nhập Môn Sư Phạm Số" },
            { code: "DIG_A4_3", level: "B1", name: "A4.3: Tự động chấm điểm & phản hồi số", statement: "Cấu hình bài Quiz tự động chấm điểm, phản hồi giải thích đáp án tức thì cho người học.", rule: "Điểm Quiz >= 70% đạt chuẩn", course: "EDU-201: Ứng Dụng Moodle Trong Dạy Học" },
            { code: "DIG_A4_4", level: "B2", name: "A4.4: Thiết lập Competency Rule đánh giá", statement: "Gán chuẩn năng lực vào Quiz và cấu hình điểm sàn đạt chuẩn (Pass Score >= 70%).", rule: "Điểm Quiz >= 70% đạt chuẩn", course: "EDU-301: Thiết Kế Khóa Học e-Learning Chuẩn" },
            { code: "DIG_A4_5", level: "C1", name: "A4.5: Phân tích học tập (Learning Analytics)", statement: "Khai thác dữ liệu sổ điểm Grader Report và phân tích chỉ số hoàn thành khóa học.", rule: "Điểm Quiz >= 70% đạt chuẩn", course: "EDU-401: Lãnh Đạo Chuyển Đổi Số Giáo Dục" },
            { code: "DIG_A4_6", level: "C2", name: "A4.6: Thiết lập ma trận đánh giá năng lực", statement: "Xây dựng khung đánh giá chuẩn đầu ra liên thông cấp bằng và chứng chỉ tự động.", rule: "Điểm Quiz >= 70% đạt chuẩn", course: "EDU-501: Chuyên Gia Sư Phạm Số MEKO" }
          ]
        },
        {
          code: "DIG_AREA5",
          name: "Lĩnh vực 5: Trao quyền cho người học (Empowering Learners)",
          en_name: "Area 5: Empowering Learners",
          description: "Đảm bảo tính tiếp cận, hòa nhập, cá nhân hóa và thúc đẩy tính chủ động của người học.",
          competencies: [
            { code: "DIG_A5_1", level: "A1", name: "A5.1: Đảm bảo học liệu dễ tiếp cận", statement: "Soạn bài giảng cỡ chữ rõ ràng, định dạng chuẩn để sinh viên dễ đọc trên điện thoại.", rule: "Điểm Quiz >= 70% đạt chuẩn", course: "EDU-101: Nhập Môn Sư Phạm Số" },
            { code: "DIG_A5_2", level: "A2", name: "A5.2: Hỗ trợ sinh viên kỹ năng số cơ bản", statement: "Hướng dẫn sinh viên cách tải bài tập, xem điểm và tham gia diễn đàn trên LMS.", rule: "Điểm Quiz >= 70% đạt chuẩn", course: "EDU-101: Nhập Môn Sư Phạm Số" },
            { code: "DIG_A5_3", level: "B1", name: "A5.3: Khuyến khích tự chủ học tập", statement: "Cung cấp học liệu tham khảo bổ sung và diễn đàn thảo luận nhóm để sinh viên tương tác.", rule: "Điểm Quiz >= 70% đạt chuẩn", course: "EDU-201: Ứng Dụng Moodle Trong Dạy Học" },
            { code: "DIG_A5_4", level: "B2", name: "A5.4: Thiết kế trải nghiệm học tập hòa nhập", statement: "Tối ưu hóa học liệu cho người học có hoàn cảnh đặc biệt (phụ đề video, âm thanh rõ).", rule: "Điểm Quiz >= 70% đạt chuẩn", course: "EDU-301: Thiết Kế Khóa Học e-Learning Chuẩn" },
            { code: "DIG_A5_5", level: "C1", name: "A5.5: Kiến tạo không gian học tập linh hoạt", statement: "Xây dựng hệ sinh thái học tập suốt đời cho người học trong toàn tổ chức.", rule: "Điểm Quiz >= 70% đạt chuẩn", course: "EDU-401: Lãnh Đạo Chuyển Đổi Số Giáo Dục" },
            { code: "DIG_A5_6", level: "C2", name: "A5.6: Định hình chiến lược trao quyền số", statement: "Đề xuất chính sách bình đẳng số và hỗ trợ người học tiếp cận tri thức mở không rào cản.", rule: "Điểm Quiz >= 70% đạt chuẩn", course: "EDU-501: Chuyên Gia Sư Phạm Số MEKO" }
          ]
        },
        {
          code: "DIG_AREA6",
          name: "Lĩnh vực 6: Thúc đẩy năng lực số học viên (Facilitating Digital Competence)",
          en_name: "Area 6: Facilitating Digital Competence",
          description: "Tạo điều kiện để người học phát triển năng lực thông tin số, giao tiếp số và sáng tạo số.",
          competencies: [
            { code: "DIG_A6_1", level: "A1", name: "A6.1: Nhắc nhở an toàn số học đường", statement: "Nhắc nhở học viên bảo mật mật khẩu tài khoản và tuân thủ quy tắc ứng xử trên mạng.", rule: "Điểm Quiz >= 70% đạt chuẩn", course: "EDU-101: Nhập Môn Sư Phạm Số" },
            { code: "DIG_A6_2", level: "A2", name: "A6.2: Hướng dẫn tra cứu thông tin học thuật", statement: "Hướng dẫn sinh viên tra cứu tài liệu học thuật có nguồn gốc rõ ràng phục vụ tiểu luận.", rule: "Điểm Quiz >= 70% đạt chuẩn", course: "EDU-101: Nhập Môn Sư Phạm Số" },
            { code: "DIG_A6_3", level: "B1", name: "A6.3: Tổ chức bài tập nhóm cộng tác số", statement: "Yêu cầu sinh viên hợp tác làm việc nhóm qua Google Docs hoặc Moodle Wiki.", rule: "Điểm Quiz >= 70% đạt chuẩn", course: "EDU-201: Ứng Dụng Moodle Trong Dạy Học" },
            { code: "DIG_A6_4", level: "B2", name: "A6.4: Thúc đẩy sáng tạo sản phẩm số", statement: "Giao đề tài yêu cầu sinh viên sáng tạo video, podcast, infographic tổng kết kiến thức.", rule: "Điểm Quiz >= 70% đạt chuẩn", course: "EDU-301: Thiết Kế Khóa Học e-Learning Chuẩn" },
            { code: "DIG_A6_5", level: "C1", name: "A6.5: Định hướng đạo đức AI & bản quyền số", statement: "Tập huấn cho học viên cách sử dụng công cụ AI tạo sinh một cách có trách nhiệm và liêm chính.", rule: "Điểm Quiz >= 70% đạt chuẩn", course: "EDU-401: Lãnh Đạo Chuyển Đổi Số Giáo Dục" },
            { code: "DIG_A6_6", level: "C2", name: "A6.6: Phát triển công dân số toàn cầu", statement: "Xây dựng chương trình phát triển năng lực số toàn diện chuẩn hóa quốc tế cho sinh viên.", rule: "Điểm Quiz >= 70% đạt chuẩn", course: "EDU-501: Chuyên Gia Sư Phạm Số MEKO" }
          ]
        }
      ]
    };

    // 3. DATA KHUNG KỸ THUẬT MEKO-IT (6 CẤP ĐỘ NĂNG LỰC LẬP TRÌNH)
    const dataMekoIT = {
      id: "mekoit",
      idnumber: "MEKO_IT_FRAMEWORK",
      name: "Khung Năng Lực Kỹ Thuật Lập Trình & Công Nghệ Số MEKO-IT (6 Cấp Độ)",
      authority: "Chuẩn Năng Lực Kỹ Sư Phần Mềm Tập Đoàn MEKOSOFT",
      csvPath: "../data/khung_nang_luc_vstep_6bac_vn.csv", // placeholder for IT csv
      description: "Khung năng lực chuyên môn kỹ thuật phân cấp 6 bậc (L1 -> L6) dành cho kỹ sư phát triển phần mềm, quản trị hệ thống và kiến trúc sư giải pháp doanh nghiệp.",
      levels: [
        { code: "A1", name: "Level 1: Intern / Fresher", desc: "Nắm vững cú pháp cơ bản, thực hiện tác vụ lập trình nhỏ dưới sự kèm cặp trực tiếp." },
        { code: "A2", name: "Level 2: Junior Engineer", desc: "Lập trình độc lập các hàm nghiệp vụ đơn giản, hiểu quy trình Git và cấu trúc dữ liệu." },
        { code: "B1", name: "Level 3: Mid-level Engineer", desc: "Tự chủ xây dựng module, thiết kế REST API chuẩn, tối ưu hóa truy vấn CSDL cơ bản." },
        { code: "B2", name: "Level 4: Senior Engineer", desc: "Làm chủ kiến trúc hệ thống, giải quyết bài toán tải cao, refactor mã nguồn phức tạp." },
        { code: "C1", name: "Level 5: Tech Lead / Staff", desc: "Dẫn dắt kỹ thuật nhóm, review mã nguồn toàn diện, thiết kế tích hợp liên hệ thống." },
        { code: "C2", name: "Level 6: Solution Architect", desc: "Hoạch định kiến trúc tổng thể phần mềm cấp tập đoàn, bảo mật và khả năng mở rộng cao." }
      ],
      domains: [
        {
          code: "MEKO_FE",
          name: "Miền 1: Frontend & Web Application Engineering",
          en_name: "Frontend Development",
          description: "Phát triển giao diện người dùng tương tác cao, thiết kế Responsive và tối ưu hóa hiệu năng trải nghiệm web.",
          competencies: [
            { code: "IT_FE_L1", level: "A1", name: "FE-L1: Cắt giao diện HTML5/CSS3 chuẩn", statement: "Chuyển đổi thiết kế Figma thành trang HTML5/CSS3 chuẩn nhận diện màu sắc MEKOSOFT.", rule: "Điểm Quiz >= 70% đạt chuẩn", course: "IT-101: Lập Trình Web Cơ Bản" },
            { code: "IT_FE_L2", level: "A2", name: "FE-L2: Xử lý JavaScript DOM & ES6+", statement: "Sử dụng JavaScript ES6+ xử lý sự kiện người dùng, validation biểu mẫu và gọi Fetch API.", rule: "Điểm Quiz >= 70% đạt chuẩn", course: "IT-102: JavaScript Nâng Cao" },
            { code: "IT_FE_L3", level: "B1", name: "FE-L3: Xây dựng React / Vite SPA Component", statement: "Xây dựng ứng dụng đơn trang React có quản lý trạng thái Hooks, Props và Router mượt mà.", rule: "Điểm Quiz >= 70% đạt chuẩn", course: "IT-201: ReactJS Chuyên Sâu" },
            { code: "IT_FE_L4", level: "B2", name: "FE-L4: Tối ưu hóa hiệu năng Web & LCP", statement: "Tối ưu hóa Core Web Vitals, giảm dung lượng bundle, lazy-loading ảnh và caching tài nguyên.", rule: "Điểm Quiz >= 70% đạt chuẩn", course: "IT-301: Tối Ưu Hóa Giao Diện Doanh Nghiệp" },
            { code: "IT_FE_L5", level: "C1", name: "FE-L5: Thiết kế Kiến Trúc Design System", statement: "Xây dựng bộ thư viện UI Components dùng chung (Design System) nhất quán toàn tập đoàn.", rule: "Điểm Quiz >= 70% đạt chuẩn", course: "IT-401: Frontend Architecture" },
            { code: "IT_FE_L6", level: "C2", name: "FE-L6: Tiên phong Micro-Frontend Doanh Nghiệp", statement: "Hoạch định kiến trúc Micro-Frontend nhúng linh hoạt vào Liferay Portal và Moodle LMS.", rule: "Điểm Quiz >= 70% đạt chuẩn", course: "IT-501: Enterprise Web Architecture" }
          ]
        },
        {
          code: "MEKO_BE",
          name: "Miền 2: Backend, Microservices & REST API",
          en_name: "Backend Engineering",
          description: "Xây dựng logic nghiệp vụ máy chủ, giao tiếp API bảo mật, xử lý tác vụ nền và tích hợp hệ thống.",
          competencies: [
            { code: "IT_BE_L1", level: "A1", name: "BE-L1: Viết script máy chủ căn bản", statement: "Viết các script PHP hoặc Java căn bản xử lý logic tính toán và đọc ghi file đơn giản.", rule: "Điểm Quiz >= 70% đạt chuẩn", course: "IT-103: Lập Trình Backend Căn Bản" },
            { code: "IT_BE_L2", level: "A2", name: "BE-L2: Xây dựng RESTful API CRUD", statement: "Xây dựng các điểm cuối REST API chuẩn HTTP methods (GET, POST, PUT, DELETE) với JSON.", rule: "Điểm Quiz >= 70% đạt chuẩn", course: "IT-202: RESTful API Engineering" },
            { code: "IT_BE_L3", level: "B1", name: "BE-L3: Liferay Service Builder & Spring Boot", statement: "Phát triển cụm dịch vụ Service Builder 5 bảng hoặc Spring Boot Service kết nối cơ sở dữ liệu.", rule: "Điểm Quiz >= 70% đạt chuẩn", course: "IT-203: Liferay 7.4 Service Builder" },
            { code: "IT_BE_L4", level: "B2", name: "BE-L4: Tích hợp xác thực OIDC & JWT", statement: "Bảo vệ API bằng JWT Bearer Token, tích hợp Single Sign-On Keycloak và phân quyền RBAC.", rule: "Điểm Quiz >= 70% đạt chuẩn", course: "IT-302: Bảo Mật & Xác Thực Hệ Thống" },
            { code: "IT_BE_L5", level: "C1", name: "BE-L5: Kiến trúc Event-Driven & Hàng Đợi", statement: "Thiết kế xử lý bất đồng bộ qua hàng đợi thông điệp (RabbitMQ/Kafka) và caching Redis.", rule: "Điểm Quiz >= 70% đạt chuẩn", course: "IT-402: Microservices & Event-Driven" },
            { code: "IT_BE_L6", level: "C2", name: "BE-L6: Kiến trúc giải pháp phân tán chịu tải", statement: "Hoạch định kiến trúc hệ thống phân tán chịu tải hàng triệu giao dịch, zero downtime.", rule: "Điểm Quiz >= 70% đạt chuẩn", course: "IT-502: Distributed Systems Architect" }
          ]
        },
        {
          code: "MEKO_DB",
          name: "Miền 3: CSDL & Kiến Trúc Dữ Liệu (Database Engineering)",
          en_name: "Database Architecture",
          description: "Thiết kế mô hình quan hệ ERD, chuẩn hóa dữ liệu, tối ưu hóa câu lệnh SQL và bảo toàn tính toàn vẹn ACID.",
          competencies: [
            { code: "IT_DB_L1", level: "A1", name: "DB-L1: Truy vấn SQL cơ bản", statement: "Thực hiện câu lệnh SELECT, INSERT, UPDATE, DELETE và lọc dữ liệu với mệnh đề WHERE.", rule: "Điểm Quiz >= 70% đạt chuẩn", course: "IT-104: Cơ Sở Dữ Liệu Quan Hệ" },
            { code: "IT_DB_L2", level: "A2", name: "DB-L2: JOIN nhiều bảng & Transaction", statement: "Viết câu lệnh INNER/LEFT JOIN phức tạp, hiểu cách sử dụng Transaction tránh mất mát dữ liệu.", rule: "Điểm Quiz >= 70% đạt chuẩn", course: "IT-204: Quản Trị CSDL Nâng Cao" },
            { code: "IT_DB_L3", level: "B1", name: "DB-L3: Thiết kế mô hình ERD chuẩn hóa 3NF", statement: "Thiết kế lược đồ quan hệ CDM/LDM chuẩn 3NF, thiết lập Primary Key và Foreign Key chặt chẽ.", rule: "Điểm Quiz >= 70% đạt chuẩn", course: "IT-204: Quản Trị CSDL Nâng Cao" },
            { code: "IT_DB_L4", level: "B2", name: "DB-L4: Đánh chỉ mục Index & Tối ưu Query", statement: "Phân tích EXPLAIN PLAN, tối ưu hóa chỉ mục B-Tree và loại bỏ full-table scans.", rule: "Điểm Quiz >= 70% đạt chuẩn", course: "IT-304: Tối Ưu Hiệu Năng CSDL" },
            { code: "IT_DB_L5", level: "C1", name: "DB-L5: Phân mảnh Partition & Sao lưu tự động", statement: "Thiết kế chiến lược Partitioning bảng dữ liệu lớn, lập kế hoạch sao lưu replication master-slave.", rule: "Điểm Quiz >= 70% đạt chuẩn", course: "IT-404: Database High Availability" },
            { code: "IT_DB_L6", level: "C2", name: "DB-L6: Kiến trúc Data Lakehouse & Big Data", statement: "Hoạch định kiến trúc dữ liệu tập trung tích hợp BigQuery, Spark và xử lý thời gian thực.", rule: "Điểm Quiz >= 70% đạt chuẩn", course: "IT-504: Enterprise Data Architecture" }
          ]
        },
        {
          code: "MEKO_DEVOPS",
          name: "Miền 4: DevOps, CI/CD & Điện Toán Đám Mây",
          en_name: "DevOps & Cloud",
          description: "Tự động hóa triển khai phần mềm, đóng gói container Docker, cấu hình hạ tầng Linux và giám sát dịch vụ.",
          competencies: [
            { code: "IT_OPS_L1", level: "A1", name: "OPS-L1: Quản trị Linux Ubuntu căn bản", statement: "Thao tác dòng lệnh Linux Bash: phân quyền tệp tin, xem tiến trình, chỉnh sửa cấu hình nano/vim.", rule: "Điểm Quiz >= 70% đạt chuẩn", course: "IT-105: Quản Trị Hệ Điều Hành Linux" },
            { code: "IT_OPS_L2", level: "A2", name: "OPS-L2: Đóng gói ứng dụng Docker", statement: "Viết Dockerfile đóng gói ứng dụng, cấu hình biến môi trường và chạy container độc lập.", rule: "Điểm Quiz >= 70% đạt chuẩn", course: "IT-205: Docker & Containerization" },
            { code: "IT_OPS_L3", level: "B1", name: "OPS-L3: Triển khai Docker Compose đa dịch vụ", statement: "Viết file docker-compose.yml khởi chạy cụm Moodle, MariaDB, Liferay và Keycloak.", rule: "Điểm Quiz >= 70% đạt chuẩn", course: "IT-205: Docker & Containerization" },
            { code: "IT_OPS_L4", level: "B2", name: "OPS-L4: Cấu hình Reverse Proxy Nginx & SSL", statement: "Thiết lập Nginx làm cổng Gateway, cấu hình chứng chỉ SSL/TLS và ánh xạ Virtual Hosts FQDN.", rule: "Điểm Quiz >= 70% đạt chuẩn", course: "IT-305: Nginx & Reverse Proxy" },
            { code: "IT_OPS_L5", level: "C1", name: "OPS-L5: Xây dựng Pipeline CI/CD tự động", statement: "Thiết lập GitHub Actions / GitLab CI tự động kiểm thử đơn vị, build jar và deploy server.", rule: "Điểm Quiz >= 70% đạt chuẩn", course: "IT-405: CI/CD Pipeline Automation" },
            { code: "IT_OPS_L6", level: "C2", name: "OPS-L6: Hạ Tầng Tự Động Hóa (Infrastructure as Code)", statement: "Hoạch định hạ tầng đám mây đa vùng, tự động mở rộng (Auto-scaling) và giám sát Prometheus/Grafana.", rule: "Điểm Quiz >= 70% đạt chuẩn", course: "IT-505: Cloud Infrastructure Architecture" }
          ]
        }
      ]
    };

    // ACTIVE STATE
    let currentFramework = dataVstep;

window.MEKO_FRAMEWORK_DATA = [dataVstep, dataDigCompEdu, dataMekoIT];
