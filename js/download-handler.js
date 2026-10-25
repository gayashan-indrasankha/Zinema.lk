document.addEventListener('DOMContentLoaded', function() {
    const downloadButton = document.getElementById('downloadBtn');
    const timerContainer = document.getElementById('timerContainer');
    
    if (!downloadButton || !timerContainer) return;

    // Disable the download button initially
    downloadButton.disabled = true;
    
    // Get the download URL from the button's data attribute
    const downloadUrl = downloadButton.dataset.downloadUrl;
    
    // Initialize download state
    let downloadInitiated = false;
    
    // Create and configure the countdown timer
    const timer = new CountdownTimer({
        duration: 10,
        onTick: (seconds) => {
            // Optional: Update any additional UI elements
            downloadButton.textContent = `Please wait ${seconds} seconds...`;
        },
        onComplete: () => {
            // Enable the download button
            downloadButton.disabled = false;
            downloadButton.textContent = 'Download Now';
            downloadButton.classList.add('active');
        },
        onStart: () => {
            // Ensure download button is disabled at start
            downloadButton.disabled = true;
            downloadButton.classList.remove('active');
        }
    });

    // Mount the timer to the container
    timer.mount(timerContainer);

    // Start the countdown immediately
    timer.start();

    // Handle download button click
    downloadButton.addEventListener('click', function(e) {
        // Prevent multiple clicks
        if (downloadInitiated) {
            e.preventDefault();
            return;
        }

        // Verify countdown is complete
        if (timer.timeLeft > 0) {
            e.preventDefault();
            return;
        }

        downloadInitiated = true;
        downloadButton.textContent = 'Starting download...';
        
        // Optional: You can either redirect or create a temporary link
        if (downloadUrl) {
            // Option 1: Direct redirect
            window.location.href = downloadUrl;
            
            // Option 2: Create temporary link (for files that need download attribute)
            /*
            const tempLink = document.createElement('a');
            tempLink.href = downloadUrl;
            tempLink.download = ''; // Browser will use the file's name
            document.body.appendChild(tempLink);
            tempLink.click();
            document.body.removeChild(tempLink);
            */
            
            // Update button state
            setTimeout(() => {
                downloadButton.textContent = 'Download Started';
                downloadButton.disabled = true;
            }, 1000);
        }
    });
});