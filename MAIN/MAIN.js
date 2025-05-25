const slider = document.querySelector('.slider');
const prevBtn = document.querySelector('.prev-btn');
const nextBtn = document.querySelector('.next-btn');
const imageContainers = document.querySelectorAll('.image-container');
const containerWidth = imageContainers[0].offsetWidth + 10; // Image width plus margin
const totalImages = imageContainers.length;

let scrollPosition = 5; // Start after the cloned elements

// Clone the first and last sets of images to create the loop effect
for (let i = 0; i < 5; i++) {
    const cloneFirst = imageContainers[i].cloneNode(true);
    const cloneLast = imageContainers[totalImages - 1 - i].cloneNode(true);
    slider.appendChild(cloneFirst);
    slider.insertBefore(cloneLast, slider.firstChild);
}

// Adjust the scroll position to start with the first original image
slider.scrollLeft = containerWidth * scrollPosition;

// Event listeners for buttons
nextBtn.addEventListener('click', () => {
    scrollPosition++;
    slider.scrollTo({
        left: containerWidth * scrollPosition,
        behavior: 'smooth'
    });

    // Reset to first image after last cloned image
    if (scrollPosition >= totalImages + 5) {
        setTimeout(() => {
            slider.style.scrollBehavior = 'auto'; // Temporarily disable smooth scrolling
            scrollPosition = 5;
            slider.scrollLeft = containerWidth * scrollPosition;
            slider.style.scrollBehavior = 'smooth'; // Re-enable smooth scrolling
        }, 300); // Delay to match the smooth scroll duration
    }
});

prevBtn.addEventListener('click', () => {
    scrollPosition--;
    slider.scrollTo({
        left: containerWidth * scrollPosition,
        behavior: 'smooth'
    });

    // Reset to last image after first cloned image
    if (scrollPosition <= 0) {
        setTimeout(() => {
            slider.style.scrollBehavior = 'auto'; // Temporarily disable smooth scrolling
            scrollPosition = totalImages;
            slider.scrollLeft = containerWidth * scrollPosition;
            slider.style.scrollBehavior = 'smooth'; // Re-enable smooth scrolling
        }, 300); // Delay to match the smooth scroll duration
    }
});


