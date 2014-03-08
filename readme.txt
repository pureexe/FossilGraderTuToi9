(เวอร์ชันนี้ข้อมูลเกี่ยวกับการติดตั้งเก่าแล้ว ให้ดูวิธีที่ได้รับการปรับปรุงที่ http://bit.ly/10RrGWv

=====================================================================
ขั้นตอนการติดตั้งระบบ
=====================================================================
1. ติดตั้ง appserv-win32-2.5.10.exe (จำ username กับ password ที่ตั้งไว้ให้ดี)
2. ค้นหาไฟล์ที่ชื่อ php.ini ในเครื่องแล้วทำการแก้ไข โดยให้ลบเครื่องหมาย ; (semicolon) หน้าบรรทัด zip.dll (ctrl+F แล้วค้นหาคำว่า zip) 
3. ทำการ restart service mysql หนึ่งรอบ
4. ทำการ copy folder ชื่อ web-submission ไปไว้ที่ Apperv/www/
5. เปิด browser (chrome หรือ firefox) แล้วไปที่ localhost/web-submission
6.-->เข้าสู่หน้า web first-config
    - กรอก username password ที่ตั้งไว้ตอนติดตั้ง appserv 
    - กรอกชื่อระบบ (ตั้งชื่อเหมือนการตั้งชื่อตัวแปร [0-9|A-Z|a-z|_])
    - ตั้ง username และ password ของ admin เพื่อใช้ในการ login ครั้งแรก
7.-->เข้าสู่หน้า login ของระบบ
    -กรอก username และ password ของ admin ที่ตั้งไว้หน้าที่แล้ว (ถ้าเข้าได้ก็เป็นอันเรียบร้อย)

=====================================================================
วิธีการใช้งาน
=====================================================================
ส่วนของ super admin (default ของระบบจะเป็น username: superadmin, password: superadmin)
คำสั่งในเมนู Admin [แสดงข้อมูล admin]
- I want to "upload" --> upload ข้อมูล admin (สังเกตดีๆ รายระเอียดของ format การสร้างไฟล์ upload จะอยู่แสดงให้เห็นในช่อง textbox ทั้งหมด แล้วเราก็สามารถ upload ข้อมูล admin คนเดียวโดยใช้ textbox นี้ได้เช่นกัน โดยไม่จำเป็นต้อง browse file)
- I want to "delete" --> สร้าง link สำหรับใช้ลบข้อมูล admin ตรง username ขึ้นมา (เอา link ออกโดยการใช้คำสั่ง delete ซ้ำ)
- I want to "download" --> download ข้อมูล admin ทั้งหมด
- สามารถ double click แถวที่ต้องการจะ แก้ไขข้อมูลเพื่อทำการแก้ไขได้ ยืนยันการแก้โดยกด enter

คำสั่งในเมนู Subject [แสดงข้อมูลรายวิชา]
- I want to "upload"*
- I want to "delete"*
- I want to "download"*
- double click*
*คล้ายกับเมนู Admin

คำสั่งในเมนู Privilage [แสดงสิทธิ์การใช้งานรายวิชา]
- click ช่องที่ต้องการให้ admin มีสิทธิ์ใช้งานได้ (เป็นแบบ toggle toggle)

=====================================================================
ส่วนของ admin (การ login ต้องทำการเลือกรายวิชาที่มีสิทธิ์สอนให้ถูกด้วย)
คำสั่งในเมนู Main [เป็นเมนูที่ใช้ในการส่ง file มาตรวจ]
- ทดลองเล่น

คำสั่งในเมนู User [แสดงข้อมูล user]
- I want to "upload"*
- I want to "delete"*
- I want to "download"*
- I want to "random" --> ใช้ในการ random password ของ user ทั้งหมดใน table ที่เห็น
- double click*
*คล้ายกับเมนู Admin ของ super admin

คำสั่งในเมนู Status [แสดงข้อมูลการ login]
- I want to "download"*
- I want to "delete"*
- I want to "approve" --> ใช้ในการ clear ประวัติการ login ของ username กับ ip (ใช้ตอนเปลี่ยน section) 

คำสั่งในเมนู Problem [แสดงข้อมูล problem]
- I want to "upload"* (content ที่ upload ทั้งหมดที่ไม่ใช่นามสกุล .in .sol .exe จะแสดงให้ผู้ใช้เห็น และหากมีการ upload file .exe ไว้ใน problem ข้อนั้นจะสามารถ เลือกใช้เป็น program ตรวจคำสั่งแทน 4 โปรแกรมที่ compare มีให้เลือก แต่เมื่อ upload แล้วต้อง refresh เมนูอีกครั้งจึงจะเห็น)
- I want to "delete"*
- I want to "download"*
- double click*
*คล้ายกับเมนู Admin ของ super admin

คำสั่งในเมนู Result [แสดงข้อมูลคะแนน]
- I want to view as <???> sort by <???> detail <???> start time "???" --> เป็นลักษณะการแสดงผลต่างๆ ให้ทดลองดู
- I want to "download" as <???> sort by <???> detail <???> start time "???" --> download ตารางที่แสดงเห็นอยู่

คำสั่งในเมนู Grader [แสดงสถานะการตรวจของ grader]
- I want to "gradeone" as <???> --> สร้าง link คล้ายการ delete แต่จะใช้เพื่อทำการส่งรายการที่เลือกไปตรวจอีกครั้ง
- I want to "gradeall" as <???> --> ส่งรายการทั้งหมดที่เห็นอยู่ไปตรวจอีกครั้ง
- I want to "clear" as <???> --> clear queue ของรายการที่กำลังรอตรวจทั้งหมด
- I want to "download" as <???>*
*คล้ายกับเมนู Admin ของ super admin

คำสั่งในเมนู Config [config ระบบ]
- Problem content download [ON|OFF] --> เปิด/ปิด การแสดงผล file ข้อมูลโจทย์ที่หน้าเมนู Main
- Last "?" source code submission download [ON|OFF] --> เปิด/ปิด การ download code เก่าที่เคย submit ไว้ ที่หน้าเมนู Main โดยสามารถกำหนดจำนวนไฟล์ย้อนหลังได้
- Check source code header [ON|OFF] --> เปิด/ปิด การตรวจสอบ header ของ code ที่ส่งเข้ามาตรวจ (เหมือนที่ใช้ตอน sutoi)
- Submission avaliable (Gradding) [ON|OFF] --> เปิด/ปิด การส่ง grader
- Printer "????????????" usable [ON|OFF] --> เปิด/ปิด การใช้งาน printer (ต้องทำการ กำหนด path ของ printer ในเครื่องด้วยว่าอยู่ที่ไหน ให้ลองทำการทดสอบโดยใช้คำสั่ง print ใน cmd ของ window ดูเมื่อสำเร็จแล้วให้นำ path ที่ใช้มาใส่) 
- Problem link bottom [ON|OFF] --> เปิด/ปิด แถบแสดงผลคะแนนด้านล่างสุดของหน้าเมนู Main
- Check IP login [ON|OFF] --> เปิด/ปิด การตรวจ check IP ในการ login (การพยายาม login ซ้อนที่แสดงในหน้าเมนู Status)

เมนู >>Grader<< [download file grader-???.bat มาเพื่อให้ทำการ run เพื่อเปิดระบบ grader]
เมนู problem.zip [download file problem.zip ที่บรรจุข้อมูลของโจทย์ทั้งหมดที่มี]
เมนู source.zip [download file source.zip ที่บรรจุข้อมูลของ sourcecode ทั้งหมดของ user ทุกคน]

>>> ให้ทดลองเล่นดู (ข้อมูลใน folder ชื่อ data-test ที่ให้มาสามารถใช้ทดลองได้

=====================================================================
ส่วนของ user (การ login ต้องทำการเลือกรายวิชาที่เรียนให้ถูกด้วย)
คำสั่งในเมนู Main [เป็นเมนูที่ใช้ในการส่ง file มาตรวจ]
- ทดลองเล่น

คำสั่งในเมนู Result [แสดงข้อมูลคะแนน เรียงตามอันดับคะแนนและจะไม่บอกรายชื่อว่าเป็นใคร]

=====================================================================
ส่วนของ supervisor (การ login ต้องทำการเลือกรายวิชาที่มีสิทธิ์ให้ถูกด้วย)
คำสั่งในเมนู Main [แสดงผลการตรวจของ user ใน group เดียวกับตัวเองในมุมมองเดียวกับที่ user เห็น]
- ทดลองเล่น

คำสั่งในเมนู Result [แสดงข้อมูลคะแนนเหมือนของ admin แต่จะแสดงเฉพาะ user ใน group เดียวกับตัวเองเท่านั้น]
- I want to view as <???> sort by <???> detail <???> start time "???"*
- I want to "download" as <???> sort by <???> detail <???> start time "???"*
*คล้ายกับเมนู Result ของ admin


=====================================================================
หมายเหตุ
=====================================================================
- ไม่ควรตั้งชื่อ problem ยาวเกิน 30 ตัวอักษร (ส่วน name ไม่เกิน 100 ตัวอักษร)
- ไม่ควรให้ username ของทุกคนในระบบซ้ำกันเด็ดขาด
- database จะมีอยู่สอง ส่วนหลักคือ master จะมีหน้าที่เก็บ admin และรายวิชาทั้งหมด อีกส่วนจะเป็น database ของรายวิชานั้น
-หากมีการเปลี่ยน username password ของ phpmyadmin ให้ไปที่ AppServ/www/web-submission/z-config.php แล้วแก้ที่ MYSQL_USER และ  MYSQL_PASSWD

=====================================================================
Credit
=====================================================================
ระบบนี้พัฒนาต่อยอดมาจากระบบ fossil grader



