async function verifyImageAjax(url, question) {
    const cacheKey = `${url}_${question}`;
    const cached = localStorage.getItem(cacheKey);
    if (cached) {
        return JSON.parse(cached);
    }
    
    try {
        const res = await fetch(url);
        if (!res.ok) throw new Error(`Failed to fetch image: ${res.status}`);
        const blob = await res.blob();
        const formData = new FormData();
        formData.append('image', blob, 'filename.jpg');
        formData.append('image_type', blob.type);
        formData.append('action', 'fileblock_verify_image');
        formData.append('question', question);
        
        const response = await fetch('/wp-admin/admin-ajax.php', {
            method: 'POST',
            body: formData,
        });
        
        if (!response.ok) throw new Error(`Verification failed: ${response.status}`);
        
        const result = await response.json();
        localStorage.setItem(cacheKey, JSON.stringify(result.result));
        return result.result;
    } catch (error) {
        console.error('Error:', error);
        return null;
    }
}

let debounceTimer;

// Debounced Verification Function
async function verifyQuestions(img) {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(async () => {
        try {
            // Show preloader or loading indicator
            showPreloader(img);

            // Critical Question: "How many eyes do you see?"
            const eyesCount = await verifyImageAjax(img.src, 'How many eyes do you see?');
            if (Number(eyesCount) > 2) {
                addVerificationMessage(img, 'Check if there is really only 1 person');
                return;
            }

            // Cascade of Prioritized Questions
            const questions = [
                { question: 'Is the image blurry?', key: 'blurry' },
                { question: 'Is the head covered?', key: 'head_covered' },
                { question: 'Are the lips pickered?', key: 'lips_pickered' },
                { question: 'Is the person wearing sunglasses?', key: 'sunglasses' }
            ];

            const tooltips = [];
            let message = '✅';

            for (const item of questions) {
                const answer = await verifyImageAjax(img.src, item.question);
                if (answer === 'yes') {
                    switch (item.key) {
                        case 'blurry':
                            tooltips.push('Blurry image');
                            message = 'Please use a clearer image.';
                            break;
                        case 'head_covered':
                            tooltips.push('Head is covered');
                            message = 'Ensure your head is uncovered.';
                            break;
                        case 'lips_pickered':
                            tooltips.push('Lips are pickered');
                            message = 'Use an image with natural expressions.';
                            break;
                        case 'sunglasses':
                            tooltips.push('Wearing sunglasses');
                            message = 'Remove sunglasses for best results.';
                            break;
                    }
                    break; // Stop after the first failed check
                }
            }

            if (tooltips.length === 0) {
                message = '✅';
            }

            addVerificationMessage(img, message, tooltips.join(', '));
        } catch (error) {
            console.error('Verification error:', error);
            addVerificationMessage(img, 'Error during verification. Please try again.');
        } finally {
            removePreloader(img);
        }
    }, 300); // 300ms debounce delay
}


function onlyUnique(value, index, array) {
    return array.indexOf(value) === index;
}


function addVerificationMessage(img, message, tooltip = '') {
    // Remove existing message if any
    const existingMessage = img.parentElement.querySelector('.verification-message');
    if (existingMessage) {
        existingMessage.remove();
    }

    // Create message element
    const msgElement = document.createElement('span');
    msgElement.className = 'verification-message';
    msgElement.textContent = message;

    if (tooltip) {
        msgElement.title = tooltip;
        msgElement.classList.add('dotted-underline'); // Assuming this class styles the tooltip
    }

    img.parentElement.appendChild(msgElement);
}

const imageQueue = [];  // Queue to store images to be processed
let isProcessing = false;  // Flag to check if an image is currently being processed

async function processQueue() {
    if (isProcessing) return;  // If already processing, do nothing
    if (imageQueue.length === 0) return;  // If no images in queue, do nothing

    isProcessing = true;
    const img = imageQueue.shift();  // Get the first image from the queue
    await verifyQuestions(img);  // Process it
    isProcessing = false;
    processQueue();  // Check if there are more images to process
}

const observerCallback = async function (mutationsList, observer) {
    for (const mutation of mutationsList) {
        if (mutation.type === 'childList') {
            for (const node of mutation.addedNodes) {
                if (node.nodeType === 1 && node.classList.contains('css-3pnu4s')) {
                    //const img = node;

                    const img = node;

                    // Prevent all events on input type file
                    img.addEventListener('click', (event) => {
                        event.preventDefault();
                        event.stopPropagation();
                        return false;
                    });

                    showPreloader(node);  // Show preloader
                    imageQueue.push(node);  // Add new image to the queue
                    processQueue();  // Process the queue

                }
            }
        }
    }
};


// Convert HEIC images to JPEG
document.addEventListener("DOMContentLoaded", function() {
    const fileInput = document.querySelector('input[type="file"]');

    fileInput.addEventListener("change", async function(event) {

        const files = Array.from(fileInput.files);
        const heicFiles = files.filter(file => file.name.endsWith('.HEIC'));
        if (heicFiles.length === 0) { return true; }

        // Prevent the default action
        event.preventDefault();
        
        // Prevent further propagation of the current event
        event.stopPropagation();

        // Main magic here!
        try {
            const convertedFiles = await Promise.all(heicFiles.map(async (image) => {
                const formData = new FormData();
                formData.append('image', image);
                formData.append('action', 'fileblock_convert_heic_image');

                const response = await fetch('/wp-admin/admin-ajax.php', {
                    method: 'POST',
                    body: formData,
                });

                if (!response.ok) {
                    throw new Error(`Verification failed: ${response.status} ${response.statusText}`);
                }

                const result = await response.json();
                const imageUrl = result.url;
                const imageName = result.filename;

                const blobResponse = await fetch(imageUrl);
                const blob = await blobResponse.blob();
                return new File([blob], imageName, { type: "image/jpeg" });
            }));

            // Add the converted files back to the input
            const dataTransfer = new DataTransfer();
            files.forEach(file => {
                if (file.name.endsWith('.HEIC')) {
                    const convertedFile = convertedFiles.shift();
                    dataTransfer.items.add(convertedFile);
                } else {
                    dataTransfer.items.add(file);
                }
            });
            fileInput.files = dataTransfer.files;

            console.log(dataTransfer.files);

            // Manually trigger the change event
            const changeEvent = new Event('change', { bubbles: true });
            fileInput.dispatchEvent(changeEvent);

            // Optional: you can do something with the chosen files here
            if (fileInput.files.length > 0) {
                console.log("Files chosen:", fileInput.files);
                // Call the function to process the queue
            }

        } catch (error) {
            console.error("Error converting HEIC images:", error);
        }
    });
});



const observerConfig = { childList: true, subtree: true };
const observerTarget = document.body;
const observer = new MutationObserver(observerCallback);
observer.observe(observerTarget, observerConfig);


function showPreloader(element) {

    // Create a div element with class "load-wrapp"
    const divElement = document.createElement('div');
    divElement.classList.add('load-wrapp');
    divElement.id = 'image-loading';

    // Create a div element with class "load-6"
    const loadDivElement = document.createElement('div');
    loadDivElement.classList.add('load-6');

    // Create a div element with class "letter-holder"
    const letterHolderDiv = document.createElement('div');
    letterHolderDiv.classList.add('letter-holder');

    // Create div elements with class "letter" for each letter in "Loading..."
    const loadingText = "Quality-check";
    for (let i = 0; i < loadingText.length; i++) {
        const letterDiv = document.createElement('div');
        letterDiv.classList.add('l-' + (i + 1));
        letterDiv.classList.add('letter');
        letterDiv.textContent = loadingText[i];
        letterHolderDiv.appendChild(letterDiv);
    }

    // Append letter holder to load div
    loadDivElement.appendChild(letterHolderDiv);

    // Append load div to load wrapp div
    divElement.appendChild(loadDivElement);

    // Insert the div element after the specified element
    element.parentNode.insertBefore(divElement, element.nextSibling);

    // Find the specific block element (ver_block) and insert the div element after it
    var ver_block = element.parentNode.parentNode.parentNode.getElementsByClassName('css-1iy0o1t')[0];
    ver_block.parentNode.insertBefore(divElement, ver_block.nextSibling);

}


function removePreloader(element) {
    const preloader = element.parentNode.parentNode.parentNode.querySelector('.load-wrapp');
    if (preloader) { preloader.remove(); }
}


function removeElementById(elementId) {
    const elementToRemove = document.getElementById(elementId);
    if (elementToRemove) {
        const parentElement = elementToRemove.parentNode;
        parentElement.removeChild(elementToRemove);
    } else {
        console.error("Element with ID '" + elementId + "' not found.");
    }
}


document.addEventListener('DOMContentLoaded', function() {
    const abbr = document.querySelector('.dotted-underline');
    
    abbr.addEventListener('click', function() {
        let tooltip = this.nextElementSibling;

        // If the tooltip doesn't exist, create it
        if (!tooltip || !tooltip.classList.contains('tooltip')) {
            tooltip = document.createElement('span');
            tooltip.textContent = this.getAttribute('data-tooltip');
            tooltip.className = 'tooltip';
            this.parentNode.insertBefore(tooltip, this.nextSibling);
        }

        // Toggle the tooltip display
        if (tooltip.style.display === 'none' || !tooltip.style.display) {
            tooltip.style.display = 'block';
        } else {
            tooltip.style.display = 'none';
        }
    });
});
