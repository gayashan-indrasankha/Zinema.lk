// =================== Subscribe Modal Logic (UPDATED) ===================
(() => {
    const modal = document.getElementById("subscribeModal");
    if (!modal) return;

    const form = document.getElementById("subscribeFormModal");
    const messageArea = document.getElementById("message-area-modal");
    const submitBtn = document.getElementById("submitBtnModal");
    const closeBtn = document.getElementById("closeSubscribeBtn");

    // Function to close the modal
    const closeModal = () => {
        modal.close();
        messageArea.innerHTML = "";
    };

    // Add listeners to close the modal
    closeBtn?.addEventListener("click", closeModal);
    modal.addEventListener("click", (e) => {
        if (e.target === modal) closeModal();
    });

    // Handle the form submission
    form?.addEventListener("submit", async (e) => {
        e.preventDefault();
        
        // First, check if user is logged in
        try {
            const sessionCheck = await fetch('api/check-session.php');
            const sessionData = await sessionCheck.json();
            
            if (!sessionData.logged_in) {
                // User not logged in - show auth modal
                modal.close();
                if (window.showAuthForSubscribe) {
                    window.showAuthForSubscribe();
                } else {
                    alert('Please login or register first to subscribe.');
                }
                return;
            }
            
            // User is logged in, proceed with subscription
            submitBtn.disabled = true;
            submitBtn.textContent = "Processing...";
            messageArea.innerHTML = "";

            const phone = document.getElementById("phone-modal").value;
            const msisdn = `tel:${phone}`;

            const response = await fetch('api/subscribe.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ msisdn: msisdn })
            });

            const data = await response.json();

            if (data.success) {
                messageArea.innerHTML = `<div class="success">${data.message}</div>`;
                form.reset();
                // Close modal after 3 seconds
                setTimeout(() => {
                    closeModal();
                    // Optionally reload page to reflect subscription status
                    location.reload();
                }, 3000);
            } else {
                throw new Error(data.message || 'Subscription failed.');
            }
        } catch (err) {
            messageArea.innerHTML = `<div class="error">Error: ${err.message}</div>`;
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = "Subscribe Now";
        }
    });
})();