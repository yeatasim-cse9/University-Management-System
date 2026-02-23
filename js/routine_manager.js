/**
 * Routine Manager Logic
 * Handles drag-and-drop scheduling, fetching, and saving.
 * Neo-Brutalist Redesign
 */

const RoutineManager = {
    apiUrl: '',
    state: {
        view: 'batch', // 'batch' or 'teacher'
        filterId: null, // current batch_id or teacher_id
        schedule: [],
        teachers: [],
        rooms: [],
        offerings: [],
        batches: []
    },

    init(url) {
        this.apiUrl = url;
        this.cacheDOM();
        this.bindEvents();
        this.fetchData();
        this.setLoading(true);
    },

    cacheDOM() {
        this.dom = {
            btnBatch: document.getElementById('btnBatchView'),
            btnTeacher: document.getElementById('btnTeacherView'),
            filterSelect: document.getElementById('viewFilter'),
            gridBody: document.getElementById('scheduleGridBody'),
            loading: document.getElementById('loadingOverlay'),

            // Modal
            modal: document.getElementById('slotModal'),
            modalTitle: document.getElementById('modalTitle'),
            form: document.getElementById('slotForm'),
            conflictAlert: document.getElementById('conflictAlert'),
            conflictMsg: document.getElementById('conflictMessage'),

            // Form Inputs
            slotId: document.getElementById('slotId'),
            daySelect: document.getElementById('daySelect'),
            roomSelect: document.getElementById('roomSelect'),
            startTime: document.getElementById('startTime'),
            endTime: document.getElementById('endTime'),
            courseSelect: document.getElementById('courseSelect'),
            teacherSelect: document.getElementById('teacherSelect'),

            // Actions
            saveBtn: document.getElementById('saveSlotBtn'),
            deleteBtn: document.getElementById('deleteSlotBtn'),
            cancelBtn: document.getElementById('cancelBtn'),
            closeBtn: document.getElementById('closeModalBtn'),
            backdrop: document.getElementById('closeModalBackdrop')
        };
    },

    bindEvents() {
        // View Toggles
        if (this.dom.btnBatch) {
            this.dom.btnBatch.addEventListener('click', () => this.switchView('batch'));
        }
        if (this.dom.btnTeacher) {
            this.dom.btnTeacher.addEventListener('click', () => this.switchView('teacher'));
        }

        // Filter Change
        if (this.dom.filterSelect) {
            this.dom.filterSelect.addEventListener('change', (e) => {
                this.state.filterId = e.target.value;
                this.renderSchedule();
            });
        }

        // Modal Actions
        if (this.dom.cancelBtn) this.dom.cancelBtn.addEventListener('click', () => this.closeModal());
        if (this.dom.closeBtn) this.dom.closeBtn.addEventListener('click', () => this.closeModal());
        if (this.dom.backdrop) this.dom.backdrop.addEventListener('click', () => this.closeModal());

        if (this.dom.saveBtn) {
            this.dom.saveBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.saveSlot();
            });
        }

        if (this.dom.deleteBtn) {
            this.dom.deleteBtn.addEventListener('click', () => {
                if (confirm('Are you sure you want to remove this class from the schedule?')) {
                    this.deleteSlot();
                }
            });
        }
    },

    setLoading(isLoading) {
        if (this.dom.loading) {
            this.dom.loading.style.display = isLoading ? 'flex' : 'none';
        }
    },

    switchView(viewType) {
        this.state.view = viewType;
        this.state.filterId = null; // Reset filter

        // Update Button Styles
        if (viewType === 'batch') {
            this.dom.btnBatch.classList.add('bg-black', 'text-white');
            this.dom.btnBatch.classList.remove('bg-white', 'text-black');
            this.dom.btnTeacher.classList.add('bg-white', 'text-black');
            this.dom.btnTeacher.classList.remove('bg-black', 'text-white');
        } else {
            this.dom.btnTeacher.classList.add('bg-black', 'text-white');
            this.dom.btnTeacher.classList.remove('bg-white', 'text-black');
            this.dom.btnBatch.classList.add('bg-white', 'text-black');
            this.dom.btnBatch.classList.remove('bg-black', 'text-white');
        }

        this.populateFilter();
        this.renderSchedule();
    },

    fetchData() {
        fetch(`${this.apiUrl}?action=fetch_all`)
            .then(res => res.json())
            .then(data => {
                if (data.error) throw new Error(data.error);

                this.state.teachers = data.teachers;
                this.state.rooms = data.rooms;
                this.state.offerings = data.offerings;
                this.state.batches = data.batches;
                this.state.schedule = data.schedule;

                this.populateDropdowns();
                this.populateFilter();
                this.renderSchedule();
                this.setLoading(false);
            })
            .catch(err => {
                console.error(err);
                if (this.dom.gridBody) {
                    this.dom.gridBody.innerHTML = `<tr><td colspan="10" class="p-8 text-center text-red-600 font-bold">Error loading data: ${err.message}</td></tr>`;
                }
                this.setLoading(false);
            });
    },

    populateDropdowns() {
        // Rooms
        if (this.dom.roomSelect) {
            this.dom.roomSelect.innerHTML = '<option value="">-- SELECT ROOM --</option>';
            this.state.rooms.forEach(room => {
                this.dom.roomSelect.innerHTML += `<option value="${room.id}">${room.room_number} (${room.type})</option>`;
            });
        }

        // Teachers
        if (this.dom.teacherSelect) {
            this.dom.teacherSelect.innerHTML = '<option value="">-- SELECT FACULTY --</option>';
            this.state.teachers.forEach(t => {
                this.dom.teacherSelect.innerHTML += `<option value="${t.id}">${t.full_name}</option>`;
            });
        }

        // Courses
        if (this.dom.courseSelect) {
            this.dom.courseSelect.innerHTML = '<option value="">-- SELECT COURSE --</option>';
            this.state.offerings.forEach(off => {
                this.dom.courseSelect.innerHTML += `<option value="${off.id}">[${off.course_code}] ${off.course_name} (${off.section})</option>`;
            });
        }
    },

    populateFilter() {
        const select = this.dom.filterSelect;
        if (!select) return;

        select.innerHTML = '<option value="">-- SHOW ALL --</option>';

        if (this.state.view === 'batch') {
            this.state.batches.forEach(b => {
                select.innerHTML += `<option value="${b.id}">${b.name}</option>`;
            });
        } else {
            this.state.teachers.forEach(t => {
                select.innerHTML += `<option value="${t.id}">${t.full_name}</option>`;
            });
        }
    },

    renderSchedule() {
        const grid = this.dom.gridBody;
        if (!grid) return;

        grid.innerHTML = '';

        const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday'];
        const times = ['08:00', '09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00'];

        days.forEach(day => {
            const tr = document.createElement('tr');
            tr.className = "hover:bg-yellow-50/50 transition-colors";

            // Day Header
            tr.innerHTML = `<td class="p-4 bg-white border-r-2 border-b-2 border-black font-black text-xs uppercase tracking-widest sticky left-0 z-10 shadow-[2px_0_0_rgba(0,0,0,0.1)] text-center w-24">${day}</td>`;

            times.forEach(time => {
                const cell = document.createElement('td');
                cell.className = "p-2 border-r-2 border-b-2 border-black/10 min-w-[140px] align-top relative h-32 group";

                // Add Button (Hidden by default, shown on hover)
                const addBtn = document.createElement('button');
                addBtn.className = "absolute inset-0 w-full h-full opacity-0 group-hover:opacity-100 flex items-center justify-center bg-black/5 transition-opacity z-0 pointer-events-none group-hover:pointer-events-auto";
                addBtn.innerHTML = '<i class="fas fa-plus text-2xl text-black/20"></i>';
                addBtn.onclick = () => this.openModal(null, { day, time });
                cell.appendChild(addBtn);

                // Find slots
                const slots = this.state.schedule.filter(s => {
                    const sTime = s.start_time.substring(0, 5);
                    const isTime = sTime >= time && sTime < this.getNextHour(time);
                    const isDay = s.day_of_week === day;

                    // Filter Logic
                    let matchesFilter = true;
                    if (this.state.filterId) {
                        if (this.state.view === 'batch') {
                            matchesFilter = s.batch_id === this.state.filterId;
                        } else {
                            matchesFilter = s.teacher_id == this.state.filterId;
                        }
                    }
                    return isDay && isTime && matchesFilter;
                });

                slots.forEach(slot => {
                    const card = this.createSlotCard(slot);
                    cell.appendChild(card);
                });

                tr.appendChild(cell);
            });
            grid.appendChild(tr);
        });
    },

    getNextHour(time) {
        const [h, m] = time.split(':');
        const nextH = parseInt(h) + 1;
        return `${nextH < 10 ? '0' + nextH : nextH}:00`;
    },

    createSlotCard(slot) {
        const el = document.createElement('div');
        // Type color coding
        const isLab = (slot.type === 'lab' || slot.type === 'practical');
        const bgColor = isLab ? 'bg-cyan-100' : 'bg-yellow-100';
        const borderColor = 'border-black';

        el.className = `relative z-10 mb-2 p-2 ${bgColor} border-2 ${borderColor} shadow-[2px_2px_0px_#000] cursor-pointer hover:-translate-y-1 hover:shadow-[4px_4px_0px_#000] transition-all`;

        el.innerHTML = `
            <div class="flex justify-between items-start mb-1">
                <span class="text-[9px] font-black uppercase bg-black text-white px-1">${slot.course_code || '---'}</span>
                 <span class="text-[9px] font-bold uppercase truncate max-w-[60px]">${slot.room_display || 'TBA'}</span>
            </div>
            <div class="text-[10px] font-bold leading-tight uppercase mb-1 line-clamp-2">${slot.course_name || 'Untitled Course'}</div>
            <div class="pt-1 mt-1 border-t border-black/10 flex justify-between items-end">
                <span class="text-[9px] font-bold text-gray-600 uppercase truncate max-w-[80px]">
                    ${this.state.view === 'batch' ? (slot.teacher_name || 'TBA') : (slot.semester_name + ' ' + slot.section)}
                </span>
                <i class="fas fa-edit text-[10px] opacity-0 group-hover:opacity-100 transition-opacity"></i>
            </div>
        `;

        el.onclick = (e) => {
            e.stopPropagation();
            this.openModal(slot);
        };

        return el;
    },

    openModal(slot = null, defaults = null) {
        this.dom.conflictAlert.classList.add('hidden');
        this.dom.modal.classList.remove('hidden');
        this.dom.modal.classList.add('flex');

        if (slot) {
            this.dom.modalTitle.textContent = "Modify Class";
            this.dom.slotId.value = slot.id;
            this.dom.daySelect.value = slot.day_of_week;
            this.dom.roomSelect.value = slot.room_id;
            this.dom.startTime.value = slot.start_time;
            this.dom.endTime.value = slot.end_time;
            this.dom.courseSelect.value = slot.course_offering_id;
            this.dom.teacherSelect.value = slot.teacher_id;

            this.dom.deleteBtn.classList.remove('hidden');
            this.dom.saveBtn.textContent = "Update Class";
        } else {
            this.dom.modalTitle.textContent = "Schedule Class";
            this.dom.form.reset();
            this.dom.slotId.value = '';

            if (defaults) {
                this.dom.daySelect.value = defaults.day;
                this.dom.startTime.value = defaults.time;
                this.dom.endTime.value = this.getNextHour(defaults.time);
            }

            this.dom.deleteBtn.classList.add('hidden');
            this.dom.saveBtn.textContent = "Confirm Schedule";
        }
    },

    closeModal() {
        this.dom.modal.classList.add('hidden');
        this.dom.modal.classList.remove('flex');
    },

    saveSlot() {
        const formData = new FormData(this.dom.form);
        const data = Object.fromEntries(formData.entries());

        // Client-side Validation
        const required = ['day', 'start_time', 'end_time', 'course_offering_id', 'teacher_id', 'room_number'];
        const missing = required.filter(field => !data[field] || data[field].trim() === '');

        if (missing.length > 0) {
            this.dom.conflictAlert.classList.remove('hidden');
            if (this.dom.conflictMsg) {
                this.dom.conflictMsg.textContent = "Please fill in all required fields: " + missing.join(', ');
            }
            return;
        }

        this.dom.saveBtn.disabled = true;
        this.dom.saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';

        // Map room_number (which contains ID) to room_id for API
        data.room_id = data.room_number;

        fetch(this.apiUrl + '?action=save_slot', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
            .then(res => res.json())
            .then(data => {
                if (data.error) {
                    this.dom.conflictAlert.classList.remove('hidden');
                    if (this.dom.conflictMsg) this.dom.conflictMsg.textContent = data.error;
                    else this.dom.conflictAlert.textContent = data.error;
                } else {
                    this.closeModal();
                    this.fetchData(); // Reload
                }
            })
            .catch(err => {
                alert('Error saving: ' + err.message);
            })
            .finally(() => {
                this.dom.saveBtn.disabled = false;
                this.dom.saveBtn.textContent = this.dom.slotId.value ? "Update Class" : "Confirm Schedule";
            });
    },

    deleteSlot() {
        const id = this.dom.slotId.value;
        if (!id) return;

        fetch(this.apiUrl + '?action=delete_slot', {
            method: 'POST',
            body: new URLSearchParams({ id: id })
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    this.closeModal();
                    this.fetchData();
                } else {
                    alert(data.error || 'Delete failed');
                }
            });
    }
};
