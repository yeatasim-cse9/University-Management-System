
📘 Dynamic Routine Allocation System
Screen-by-Screen Functional Specification
🧩 SYSTEM OVERVIEW

The purpose of this system is:

The Department Admin will dynamically create class routines.

The system will automatically detect conflicts and generate valid routines.

The Admin can manually edit the routine if needed.

Teachers and Students can only view the routine.

👥 ROLES

Super Admin

Department Admin

Teacher

Student

🖥️ SCREEN-1: Routine Dashboard
🎯 Purpose

To provide full control of the routine system to the Department Admin.

UI Elements

Button: Create New Routine

Button: Manage Time Slots

Button: Manage Courses

Button: Manage Teachers

Button: Manage Rooms

Button: Generate Routine

Button: View Routine

Button: Publish Routine

Permissions

Accessible only by Department Admin and Super Admin.

🖥️ SCREEN-2: Academic Structure Setup
🎯 Purpose

To define the academic context for which the routine will be created.

Fields

Department (dropdown)

Academic Year (dropdown)

Semester (dropdown)

Section (optional)

Actions

Save

Edit

Delete

🖥️ SCREEN-3: Time Slot Management
🎯 Purpose

To define class time slots.

UI Table
Day	Start Time	End Time	Slot Name
Sunday	08:30	10:00	Slot-1
Sunday	10:00	11:30	Slot-2
Actions

Add Slot

Edit Slot

Delete Slot

Activate / Deactivate Slot

Rules

Overlapping time slots are not allowed.

Each slot must be unique.

🖥️ SCREEN-4: Course Configuration
🎯 Purpose

To configure courses for each semester.

Fields

Course Code

Course Name

Semester

Weekly Class Count

Duration per Class

Course Type (Theory / Lab)

Student Count (optional)

Actions

Add Course

Edit Course

Delete Course

🖥️ SCREEN-5: Teacher Assignment
🎯 Purpose

To assign teachers to courses.

Teacher List Table
Teacher Name	Assigned Courses	Max Classes/Day	Availability
Actions

Assign Course

Set Availability

Set Max Load

Availability Setup

Teachers can mark unavailable time by:

Day

Slot

🖥️ SCREEN-6: Room Management
🎯 Purpose

To define classrooms and labs.

Fields

Room Number

Room Type (Theory / Lab)

Capacity

Available Slots

Actions

Add Room

Edit Room

Delete Room

🖥️ SCREEN-7: Rule Configuration (Critical)
🎯 Purpose

To define the rules for routine generation.

Priority Rules

Lab courses are allocated first.

Courses with higher weekly class counts are allocated first.

Courses with limited available teachers are allocated first.

Constraints

A teacher cannot teach two classes in the same time slot.

A room cannot be used by two classes in the same time slot.

A semester cannot have two classes in the same time slot.

Teacher maximum classes per day must not be exceeded.

Room type must match the course type.

Minimum break time between classes must be maintained.

Actions

Enable / Disable rules

Edit rule values

🖥️ SCREEN-8: Generate Routine (Core Screen)
🎯 Purpose

To automatically generate the routine.

UI Elements

Button: Generate Routine

Button: Reset Routine

Progress Indicator

⚙️ SYSTEM PROCESS (Logic Specification)
Step-1: Load Data

The system loads:

Courses

Teachers

Rooms

Time slots

Rules

Step-2: Sort Courses (Priority Order)

Order of priority:

Lab courses

Courses with higher weekly class counts

Courses with limited teachers

Normal courses

Step-3: Allocation Loop

For each course:

Identify eligible teachers.

Identify available time slots.

Identify suitable rooms.

Check for conflicts.

If valid → allocate the course.
If not valid → try the next available slot.
If no valid slot is found → mark the course as “unallocated”.

Step-4: Conflict Check Logic

For each candidate slot, the system verifies:

Teacher conflict

Room conflict

Semester conflict

Teacher workload limit

Room type compatibility

If all conditions pass → assign the slot.

🖥️ SCREEN-9: Routine Preview
🎯 Purpose

To display the generated routine.

UI Table
Day	Slot	Course	Teacher	Room	Semester
Actions

Edit Routine

Swap Classes

Delete Slot

Reassign Teacher

Reassign Room

🖥️ SCREEN-10: Manual Edit Screen
🎯 Purpose

To allow manual modification of the routine.

Features

Drag and Drop classes

Change teacher

Change room

Change slot

Validation

Show warnings if conflicts occur.

Block invalid changes.

🖥️ SCREEN-11: Conflict Report Screen
🎯 Purpose

To display courses that could not be allocated.

UI Table
Course	Reason
CSE-301	No available teacher
CSE-302	No free room
Actions

Try Re-generate

Manual Assign

🖥️ SCREEN-12: Publish Routine
🎯 Purpose

To publish the final routine.

Actions

Publish

Unpublish

Lock Routine

After Publishing

Teachers and Students can view the routine.

Only Admins can edit it.

👨‍🏫 TEACHER VIEW SCREEN
Features

My Routine

Free Slots

Workload Summary

👨‍🎓 STUDENT VIEW SCREEN
Features

Semester Routine

Room Information

Teacher Information

📌 DEVELOPER INSTRUCTIONS (Critical Requirements)
Mandatory Conditions

Routine generation must be reversible (reset must be possible).

Manual edits must follow the same validation rules as automatic allocation.

Every allocation must be logged.

Conflict reasons must be stored.

Routine must be versioned (v1, v2, v3, etc.).
