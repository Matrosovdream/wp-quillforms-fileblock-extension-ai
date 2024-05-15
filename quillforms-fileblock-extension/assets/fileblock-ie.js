async function verifyImageAjax(url, question) {
    try {
        const res = await fetch(url);
        if (!res.ok) {
            throw new Error(`Failed to fetch image: ${res.status} ${res.statusText}`);
        }
        const blob = await res.blob();
        const type = blob.type;

        const formData = new FormData();
        formData.append('image', blob, 'filename.jpg');
        formData.append('image_type', type);
        formData.append('action', 'fileblock_verify_image');
        formData.append('question', question);

        const response = await fetch('/wp-admin/admin-ajax.php', {
            method: 'POST',
            body: formData,
        });

        if (!response.ok) {
            throw new Error(`Verification failed: ${response.status} ${response.statusText}`);
        }

        const result = await response.json();
        return result.result;
    } catch (error) {
        console.error('Error:', error);
        return null;
    }
}

async function verifyQuestions(img) {

    try {
        // First check for multiple faces
        const facesCount = await verifyImageAjax(img.src, 'how many close up faces do you see?');
        if (Number(facesCount) > 1) {
            // More than one close-up face detected, skip further checks and show message
            addVerificationMessage(img, 'For best results use an image', 'with only one face visible');
        } else {

            /*
            var isScreenshot = await verifyImageAjax(img.src, 'Is it a screenshot?');
            if( isScreenshot == 'yes' ) {
                addVerificationMessage(img, 'Probably not the best. Why?', 'screenshot');
                return;
            }
            */

            var isPerson = await verifyImageAjax(img.src, 'Is there a person in the picture?');
            if( isPerson == 'no' ) {
                addVerificationMessage(img, 'For best results use an image', 'with the face clearly visible');
                return;
            }

            // If not more than one face, proceed with other verifications
            const promises = [
                verifyImageAjax(img.src, 'Are the lips pickered?'),
                verifyImageAjax(img.src, 'Does the person wear sunglasses?'),
                verifyImageAjax(img.src, 'Is it an official document?'),
                verifyImageAjax(img.src, 'Is the head covered?'),
                //verifyImageAjax(img.src, 'Is there a person in the picture?'),
                verifyImageAjax(img.src, 'Is the image blurry?'),
            ];

            const outputs = await Promise.all(promises);

            var tooltips = [];
            var message = '';
            outputs.forEach((output, index) => {
                if (output !== null) {
                    switch (index) {
                        case 0:
                            if (output === 'yes') {
                                tooltips.push('with natural expression, no grimaces');
                                message = 'For best results use an image';
                            }
                            break;
                        case 1:
                            if (output === 'yes') {
                                tooltips.push('without sunglasses');
                                message = 'For best results use an image';
                            }
                            break;
                        case 2:
                            if (output === 'yes') {
                                tooltips.push('not a document or other');
                                message = 'For best results use an image';
                            }
                            break;
                        case 3:
                            if (output === 'yes') {
                                tooltips.push('with your face and head uncovered');
                                message = 'For best results use an image';
                            }
                            break;
                        case 4:
                            if (output === 'yes') {
                                tooltips.push('Blurry image');
                                message = 'Probably not the best. Why?';
                            }
                            break;
                        case 5:
                        
                    }
                }
            });

            //tooltips = [];
            //console.log( tooltips.length );

            if( tooltips.length == 0 ) {
                message = '✅';
            }

            tooltips = tooltips.filter(onlyUnique);

            addVerificationMessage(img, message, tooltips.join(', '));
        }
    } catch (error) {
        console.error('Verification error:', error);
    } finally {
        removePreloader(img);
        //removeElementById("image-loading");
    }
}


function onlyUnique(value, index, array) {
    return array.indexOf(value) === index;
}


function addVerificationMessage(element, message, tooltip) {
    
    // Create a div element with class "omg"
    const divElement = document.createElement('div');
    divElement.classList.add('omg');

    // Create an abbr element with the specified title and class
    const abbrElement = document.createElement('p');
    //abbrElement.title = tooltip;
    //abbrElement.rel = 'tooltip';

    if( tooltip == '' ) {
        abbrElement.classList.add('image-ok');
    } else {
        //abbrElement.classList.add('dotted-underline');
    }

    abbrElement.textContent = message; // Set the text content to the provided message

    // Append the abbr element to the div element
    divElement.appendChild(abbrElement);


    // Create an abbr element with the specified title and class
    const abbrElement2 = document.createElement('div');
    abbrElement2.title = tooltip;
    //abbrElement2.rel = 'tooltip';
    abbrElement2.classList.add('tooltip');
    abbrElement2.textContent = tooltip; // Set the text content to the provided message

    // Append the abbr element to the div element
    divElement.appendChild(abbrElement2);



    // Insert the div element after the specified element
    element.parentNode.insertBefore(divElement, element.nextSibling);

    // Find the specific block element (ver_block) and insert the div element after it
    var ver_block = element.parentNode.parentNode.parentNode.getElementsByClassName('css-1iy0o1t')[0];
    ver_block.parentNode.insertBefore(divElement, ver_block.nextSibling);

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

                    showPreloader(node);  // Show preloader
                    imageQueue.push(node);  // Add new image to the queue
                    processQueue();  // Process the queue

                }
            }
        }
    }
};

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
