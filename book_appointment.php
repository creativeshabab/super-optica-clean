<?php require_once 'includes/header.php'; ?>

<div class="web-wrapper section-padding">
<div class="container">
    <div class="booking-container">
        <h2 class="text-center mb-5 text-accent font-black">Book Your Eye Test</h2>
        
        <div class="step-indicator">
            <div class="booking-step active" id="step1-ind">1</div>
            <div class="booking-step" id="step2-ind">2</div>
            <div class="booking-step" id="step3-ind">3</div>
        </div>

        <form id="bookingForm">
            <!-- Step 1: Select Date -->
            <div id="step1">
                <h4 class="mb-4 font-bold">Select a Date</h4>
                <div class="form-group">
                    <input type="date" id="dateInput" class="form-input text-lg" min="<?= date('Y-m-d') ?>" required>
                </div>
                <button type="button" class="btn btn-primary w-full mt-6" onclick="loadSlots()">Next: Find Slots</button>
            </div>

            <!-- Step 2: Select Time -->
            <div id="step2" class="hidden">
                <h4 class="mb-4 font-bold">Select Time Slot</h4>
                <p id="slotsMessage" class="text-muted text-sm pb-2">Loading availability...</p>
                <div id="slotsContainer" class="slots-grid"></div>
                <input type="hidden" id="selectedSlot">
                
                <div class="flex gap-3 mt-8">
                    <button type="button" class="btn btn-outline flex-1" onclick="showStep(1)">Back</button>
                    <button type="button" class="btn btn-primary flex-1" id="btnStep2" disabled onclick="showStep(3)">Next: Your Details</button>
                </div>
            </div>

            <!-- Step 3: User Details -->
            <div id="step3" class="hidden">
                <h4 class="mb-4 font-bold">Your Information</h4>
                <div class="form-group mb-4">
                    <label class="form-label">Full Name <span style="color:red">*</span></label>
                    <input type="text" id="nameInput" class="form-input" required placeholder="John Doe">
                </div>
                <div class="form-group mb-4">
                    <label class="form-label">Phone Number <span style="color:red">*</span></label>
                    <input type="tel" id="phoneInput" class="form-input" required pattern="[0-9]{10}" title="Please enter a valid 10-digit mobile number" placeholder="9876543210">
                </div>
                <div class="form-group mb-6">
                    <label class="form-label">Email Address <span style="color:red">*</span></label>
                    <input type="email" id="emailInput" class="form-input" required placeholder="your@email.com">
                </div>

                <div class="flex gap-3 mt-8">
                    <button type="button" class="btn btn-outline flex-1" onclick="showStep(2)">Back</button>
                    <button type="submit" class="btn btn-primary flex-1">Confirm Booking</button>
                </div>
            </div>
            
            <!-- Success Message -->
            <div id="successMessage" class="hidden text-center">
                <div class="icon-circle-lg bg-success-light text-success">
                    <i class="fa-solid fa-check"></i>
                </div>
                <h3 class="text-success text-2xl font-black mb-2">Booking Confirmed!</h3>
                <p class="text-muted leading-relaxed">We have scheduled your eye test. See you soon!</p>
                <a href="index.php" class="btn btn-primary mt-8">Return Home</a>
            </div>
        </form>
    </div>
</div>
</div>

<script>
    let currentStep = 1;

    function showStep(step) {
        document.getElementById(`step1`).classList.add('hidden');
        document.getElementById(`step2`).classList.add('hidden');
        document.getElementById(`step3`).classList.add('hidden');
        document.getElementById(`step${step}`).classList.remove('hidden');
        
        // Update Indicators
        for(let i=1; i<=3; i++) {
            const el = document.getElementById(`step${i}-ind`);
            if (i < step) {
                el.classList.add('completed');
                el.classList.remove('active');
                el.innerHTML = '<i class="fa-solid fa-check"></i>';
            } else if (i === step) {
                el.classList.add('active');
                el.classList.remove('completed');
                el.innerHTML = i;
            } else {
                el.classList.remove('active', 'completed');
                el.innerHTML = i;
            }
        }
        currentStep = step;
    }

    async function loadSlots() {
        const date = document.getElementById('dateInput').value;
        if (!date) {
            alert('Please select a date first');
            return;
        }

        document.getElementById('slotsMessage').innerText = 'Checking availability...';
        showStep(2);
        const container = document.getElementById('slotsContainer');
        container.innerHTML = '';

        try {
            const res = await fetch(`api/appointment_slots.php?date=${date}`);
            const data = await res.json();

            if (data.error) {
                container.innerHTML = `<div class="col-span-full text-error text-center">${data.error}</div>`;
                return;
            }

            if (data.message) {
                 container.innerHTML = `<div class="col-span-full text-error text-center">${data.message}</div>`;
                 return;
            }

            if (data.slots.length === 0) {
                container.innerHTML = `<div class="col-span-full text-muted text-center">No slots available on this date.</div>`;
                return;
            }

            document.getElementById('slotsMessage').innerText = `Available slots for ${new Date(date).toDateString()}`;

            data.slots.forEach(slot => {
                const div = document.createElement('div');
                div.className = 'time-slot';
                div.innerText = slot.time;
                div.onclick = () => selectSlot(div, slot.time);
                container.appendChild(div);
            });

        } catch (e) {
            console.error(e);
            container.innerHTML = '<div class="text-error col-span-full text-center">Failed to load slots. Please try again.</div>';
        }
    }

    function selectSlot(el, time) {
        document.querySelectorAll('.time-slot').forEach(d => d.classList.remove('selected'));
        el.classList.add('selected');
        document.getElementById('selectedSlot').value = time;
        document.getElementById('btnStep2').disabled = false;
    }

    document.getElementById('bookingForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const payload = {
            date: document.getElementById('dateInput').value,
            time_slot: document.getElementById('selectedSlot').value,
            name: document.getElementById('nameInput').value,
            phone: document.getElementById('phoneInput').value,
            email: document.getElementById('emailInput').value
        };

        try {
            const res = await fetch('api/book_appointment.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(payload)
            });
            const result = await res.json();

            if (result.success) {
                document.getElementById('step3').classList.add('hidden');
                document.querySelector('.step-indicator').classList.add('hidden');
                document.getElementById('successMessage').classList.remove('hidden');
                
                // Show Toast as requested
                if (typeof showToast === 'function') {
                    showToast('Appointment Confirmed! Check your email.', 'success');
                }
            } else {
                alert(result.error || 'Booking failed');
            }
        } catch (e) {
            alert('Something went wrong. Please try again.');
        }
    });
</script>

<?php require_once 'includes/footer.php'; ?>
