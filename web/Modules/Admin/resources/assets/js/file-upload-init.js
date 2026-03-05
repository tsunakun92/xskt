/**
 * FilePond initialization for file upload component
 * Handles file upload, removal, and existing file management
 */

/**
 * Hide FilePond "upload cancelled" status message blocks.
 *
 * @param {ParentNode} root
 * @param {number} retryCount - Number of retries to find and hide statuses
 * @return {boolean} - Returns true if any status was hidden
 */
function hideUploadCancelledStatuses(root = document, retryCount = 0) {
    const statusMains = root.querySelectorAll('.filepond--file-status-main');
    let hiddenCount = 0;

    statusMains.forEach((status) => {
        const text = (status.textContent || '').trim().toLowerCase();
        if (!text.startsWith('upload cancel')) {
            return;
        }

        const statusWrapper = status.closest('.filepond--file-status');
        if (statusWrapper && statusWrapper.style.display !== 'none') {
            statusWrapper.style.display = 'none';
            hiddenCount++;
        }

        const fileItem = status.closest('.filepond--file');
        if (!fileItem) {
            return;
        }

        const processActionButton = fileItem.querySelector(
            '.filepond--file-action-button.filepond--action-process-item',
        );
        if (processActionButton && processActionButton.style.display !== 'none') {
            processActionButton.style.display = 'none';
        }
    });

    // Retry mechanism: if we found statuses and haven't exceeded retry limit, try again after delay
    if (hiddenCount > 0 && retryCount < 3) {
        setTimeout(() => {
            hideUploadCancelledStatuses(root, retryCount + 1);
        }, 100 * (retryCount + 1));
    }

    return hiddenCount > 0;
}

/**
 * Initialize FilePond for a file input element
 *
 * @param {HTMLElement} input - The file input element
 * @param {Object} options - Configuration options
 * @param {Array} options.existingFiles - Array of existing files to load
 * @param {number} options.maxFileSizeBytes - Maximum file size in bytes
 * @param {number} options.maxFiles - Maximum number of files
 * @param {boolean} options.multiple - Whether multiple files are allowed
 * @param {string} options.uploadUrl - URL for file upload
 * @param {string} options.removeUrl - URL template for file removal
 * @param {string} options.csrfToken - CSRF token
 * @param {string} options.tmpFilesInputId - ID of hidden input for tmp files
 * @param {string} options.deletedFilesInputId - ID of hidden input for deleted files
 */
export function initFilePondUpload(input, options) {
    if (!input || typeof FilePond === 'undefined') {
        return null;
    }

    const {
        existingFiles = [],
        maxFileSizeBytes = 5242880,
        maxFiles = 10,
        multiple = true,
        uploadUrl,
        removeUrl,
        csrfToken,
        tmpFilesInputId,
        deletedFilesInputId,
    } = options;

    const tmpFilesInput = document.getElementById(tmpFilesInputId);
    const deletedFilesInput = document.getElementById(deletedFilesInputId);
    const tmpFiles = new Set();
    const deletedFileIds = new Set();

    const serverConfig = {
        process: {
            url: uploadUrl,
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
            },
            withCredentials: false,
            ondata: (formData) => {
                const fileInput = formData.get('filepond');
                if (fileInput && fileInput.name) {
                    const existingFile = existingFiles?.find(f => {
                        const existingFileName = f.options?.file?.name || f.source.split('/').pop();
                        return existingFileName === fileInput.name;
                    });
                    if (existingFile) {
                        return false;
                    }
                }
                return formData;
            },
            onload: (response) => {
                try {
                    const data = JSON.parse(response);
                    if (data.error) {
                        throw new Error(data.message || 'Upload failed');
                    }
                    tmpFiles.add(data.filename);
                    updateTmpFilesInput();
                    return data.filename;
                } catch (e) {
                    console.error('Error parsing upload response:', e);
                    throw e;
                }
            },
            onerror: (response) => {
                try {
                    const data = JSON.parse(response);
                    return data.message || 'Upload failed';
                } catch {
                    return 'Upload failed';
                }
            },
        },
        revert: (uniqueFileId, load, error) => {
            const url = removeUrl.replace('FILENAME_PLACEHOLDER', uniqueFileId);
            fetch(url, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                },
            })
                .then((response) => response.json())
                .then((data) => {
                    if (data.error) {
                        error(data.message || 'Failed to remove file');
                    } else {
                        tmpFiles.delete(uniqueFileId);
                        updateTmpFilesInput();
                        load();
                    }
                })
                .catch((err) => {
                    error(err.message || 'Failed to remove file');
                });
        },
        load: (source, load, error) => {
            if (!source || typeof source !== 'string') {
                error('Invalid file source');
                return;
            }

            if (source.startsWith('/api/') || source.includes('/api/')) {
                error('Cannot load from API route');
                return;
            }

            let fileType = null;
            let fileName = null;
            if (existingFiles && Array.isArray(existingFiles)) {
                const existingFile = existingFiles.find(f => f.source === source);
                if (existingFile && existingFile.options && existingFile.options.file) {
                    fileType = existingFile.options.file.type;
                    fileName = existingFile.options.file.name;
                }
            }

            fetch(source, {
                method: 'GET',
                headers: {
                    'Accept': 'image/*,application/pdf,*/*',
                },
            })
                .then((response) => {
                    if (!response.ok) {
                        throw new Error(`Failed to load file: ${response.statusText} (${response.status})`);
                    }
                    const contentType = fileType || response.headers.get('Content-Type') || 'application/octet-stream';
                    return response.blob().then(blob => ({
                        blob,
                        contentType
                    }));
                })
                .then(({ blob, contentType }) => {
                    const urlParts = source.split('/');
                    const filename = fileName || urlParts[urlParts.length - 1] || 'file';
                    const file = new File([blob], filename, {
                        type: contentType
                    });
                    load(file);
                })
                .catch((err) => {
                    console.error('Error loading file:', err);
                    error('Failed to load file: ' + err.message);
                });
        },
    };

    const config = {
        allowMultiple: multiple,
        server: serverConfig,
        allowImagePreview: true,
        imagePreviewHeight: 256,
        imageCropAspectRatio: 1,
        imageResizeTargetWidth: 1280,
        imageResizeTargetHeight: 1280,
        imageResizeMode: 'contain',
        imageResizeUpscale: false,
    };

    if (multiple && maxFiles > 0) {
        config.maxFiles = maxFiles;
    }

    if (typeof FilePondPluginFileValidateSize !== 'undefined' && maxFileSizeBytes > 0) {
        const maxFileSizeMB = Math.round(maxFileSizeBytes / (1024 * 1024));
        config.maxFileSize = maxFileSizeMB + 'MB';
    }

    const pond = FilePond.create(input, config);

    // Hide "upload cancelled" messages if they appear (commonly happens for existing files we abort processing).
    if (pond && pond.element && typeof MutationObserver !== 'undefined') {
        let debounceTimer = null;
        const debouncedHide = () => {
            if (debounceTimer) {
                clearTimeout(debounceTimer);
            }
            debounceTimer = setTimeout(() => {
                hideUploadCancelledStatuses(pond.element);
            }, 50);
        };

        const observer = new MutationObserver(debouncedHide);
        observer.observe(pond.element, { childList: true, subtree: true, characterData: true });

        // Initial hide with multiple delays to catch all cases
        const hideWithDelays = () => {
            hideUploadCancelledStatuses(pond.element);
            setTimeout(() => hideUploadCancelledStatuses(pond.element), 50);
            setTimeout(() => hideUploadCancelledStatuses(pond.element), 150);
            setTimeout(() => hideUploadCancelledStatuses(pond.element), 300);
        };

        requestAnimationFrame(() => {
            hideWithDelays();
        });
    }

    if (existingFiles && Array.isArray(existingFiles) && existingFiles.length > 0) {
        existingFiles.forEach(async (fileItem) => {
            try {
                let source = fileItem.source.replace(/([^:])\/\/+/g, '$1/');
                const fileInfo = fileItem.options?.file || {};
                const fileType = fileInfo.type || 'image/jpeg';
                const fileName = fileInfo.name || source.split('/').pop();

                const response = await fetch(source);
                if (!response.ok) {
                    throw new Error('Failed to load file: ' + response.statusText);
                }

                const contentType = fileType || response.headers.get('Content-Type') || 'image/jpeg';
                const blob = await response.blob();
                const file = new File([blob], fileName, {
                    type: contentType
                });

                const serverId = fileItem.options?.metadata?.serverId || fileItem.options?.metadata?.id;
                const addedFilePromise = pond.addFile(file, {
                    metadata: {
                        ...fileItem.options?.metadata,
                        serverId: serverId,
                        isExisting: true,
                    },
                });

                if (addedFilePromise && typeof addedFilePromise.then === 'function') {
                    addedFilePromise.then((addedFileItem) => {
                        if (addedFileItem) {
                            const setStatus = () => {
                                try {
                                    if (addedFileItem.status !== undefined) {
                                        addedFileItem.status = FilePond.FileStatus.PROCESSING_COMPLETE;
                                    }
                                    if (addedFileItem.serverId !== undefined) {
                                        addedFileItem.serverId = serverId;
                                    }
                                    const fileElement = addedFileItem.element;
                                    if (fileElement) {
                                        fileElement.classList.add('filepond--file-existing');
                                    }

                                    const root = pond.element || document;
                                    hideUploadCancelledStatuses(root);
                                } catch (e) {
                                    // Ignore errors
                                }
                            };
                            setStatus();
                            setTimeout(() => {
                                setStatus();
                                setTimeout(() => hideUploadCancelledStatuses(pond.element || document), 50);
                            }, 10);
                            requestAnimationFrame(() => {
                                setStatus();
                                setTimeout(() => hideUploadCancelledStatuses(pond.element || document), 100);
                            });
                        }
                    }).catch((error) => {
                        console.error('Error adding existing file:', error);
                    });
                }
            } catch (error) {
                console.error('Error loading existing file:', error);
            }
        });
    }

    pond.on('addfile', (error, file) => {
        if (error) {
            console.error('FilePond addfile error:', error);
            return;
        }

        const metadata = file.getMetadata ? file.getMetadata() : {};
        const serverId = metadata?.serverId || metadata?.id;
        if (serverId || metadata?.isExisting) {
            if (typeof file.abortProcessing === 'function') {
                file.abortProcessing();
            }

            const preventProcessing = () => {
                try {
                    if (file.status !== undefined) {
                        file.status = FilePond.FileStatus.PROCESSING_COMPLETE;
                    }
                    if (file.serverId !== undefined) {
                        file.serverId = serverId;
                    }
                    if (file._setStatus && typeof file._setStatus === 'function') {
                        file._setStatus(FilePond.FileStatus.PROCESSING_COMPLETE);
                    }
                    const fileElement = file.element;
                    if (fileElement) {
                        fileElement.classList.add('filepond--file-existing');
                    }

                    const root = pond.element || document;
                    hideUploadCancelledStatuses(root);
                } catch (e) {
                    // Ignore errors
                }
            };

            preventProcessing();
            Promise.resolve().then(() => {
                preventProcessing();
                setTimeout(() => hideUploadCancelledStatuses(pond.element || document), 50);
            });
            setTimeout(() => {
                preventProcessing();
                setTimeout(() => hideUploadCancelledStatuses(pond.element || document), 100);
            }, 0);
            requestAnimationFrame(() => {
                setTimeout(() => hideUploadCancelledStatuses(pond.element || document), 150);
            });
        }
    });

    pond.on('processfilestart', (file) => {
        const metadata = file.getMetadata();
        const serverId = metadata?.serverId || metadata?.id;
        if (serverId) {
            if (typeof file.abortProcessing === 'function') {
                file.abortProcessing();
            }
            if (file.status !== undefined) {
                file.status = FilePond.FileStatus.PROCESSING_COMPLETE;
            }
        }
    });

    pond.on('removefile', (error, file) => {
        if (error) {
            console.error('Remove file error:', error);
            return;
        }

        const metadata = file?.getMetadata ? file.getMetadata() : {};
        const serverId = metadata?.serverId || metadata?.id || file?.serverId;

        if (serverId && !isNaN(serverId) && Number.isInteger(Number(serverId))) {
            deletedFileIds.add(String(serverId));
            updateDeletedFilesInput();
        } else {
            const fileId = file?.server?.id || file?.id;
            if (fileId) {
                tmpFiles.delete(fileId);
                updateTmpFilesInput();

                const url = removeUrl.replace('FILENAME_PLACEHOLDER', fileId);
                fetch(url, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                    },
                })
                    .then((response) => response.json())
                    .then((data) => {
                        if (data.error) {
                            console.error('Error removing tmp file:', data.message);
                        }
                    })
                    .catch((err) => {
                        console.error('Error removing tmp file:', err);
                    });
            }
        }
    });

    function updateTmpFilesInput() {
        if (tmpFilesInput) {
            tmpFilesInput.value = Array.from(tmpFiles).join(',');
        }
    }

    function updateDeletedFilesInput() {
        if (deletedFilesInput) {
            deletedFilesInput.value = Array.from(deletedFileIds).join(',');
        }
    }

    return pond;
}

// Export to window for use in blade templates
if (typeof window !== 'undefined') {
    window.initFilePondUpload = initFilePondUpload;
}
