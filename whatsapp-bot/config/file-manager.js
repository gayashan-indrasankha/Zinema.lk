/**
 * Local File Manager
 * Manages local video files for the Ultimate Multi-Bot System
 * 
 * Directory Structure:
 * D:\bot-media\
 * ├── movies\{id}\full.mp4 or part_1.mp4, part_2.mp4
 * └── episodes\{id}\full.mp4 or part_1.mp4, part_2.mp4
 */

const fs = require('fs');
const path = require('path');

class FileManager {
    constructor(basePath = 'D:\\bot-media') {
        this.basePath = basePath;
        this.ensureDirectories();
    }

    /**
     * Ensure base directories exist
     */
    ensureDirectories() {
        const dirs = [
            this.basePath,
            path.join(this.basePath, 'movies'),
            path.join(this.basePath, 'episodes'),
            path.join(this.basePath, 'temp')
        ];

        dirs.forEach(dir => {
            if (!fs.existsSync(dir)) {
                fs.mkdirSync(dir, { recursive: true });
                console.log(`📁 Created directory: ${dir}`);
            }
        });
    }

    /**
     * Get file path for content
     * @param {string} contentType - 'movie' or 'episode'
     * @param {number} contentId - Content ID from database
     * @param {number|null} partNumber - Part number (null for full file)
     * @returns {string|null} File path or null if not found
     */
    getFilePath(contentType, contentId, partNumber = null) {
        const typeFolder = contentType === 'movie' ? 'movies' : 'episodes';
        const contentFolder = path.join(this.basePath, typeFolder, String(contentId));

        // Check if content folder exists
        if (!fs.existsSync(contentFolder)) {
            return null;
        }

        // Determine file name
        let fileName;
        if (partNumber && partNumber > 0) {
            fileName = `part_${partNumber}.mp4`;
        } else {
            fileName = 'full.mp4';
        }

        const filePath = path.join(contentFolder, fileName);

        // Check if file exists
        if (fs.existsSync(filePath)) {
            return filePath;
        }

        // Try alternative extensions
        const extensions = ['.mkv', '.avi', '.mov', '.webm'];
        for (const ext of extensions) {
            const altPath = filePath.replace('.mp4', ext);
            if (fs.existsSync(altPath)) {
                return altPath;
            }
        }

        return null;
    }

    /**
     * Check if local file exists for content
     * @param {string} contentType - 'movie' or 'episode'
     * @param {number} contentId - Content ID
     * @param {number|null} partNumber - Part number
     * @returns {boolean}
     */
    hasLocalFile(contentType, contentId, partNumber = null) {
        return this.getFilePath(contentType, contentId, partNumber) !== null;
    }

    /**
     * Get file info (size, last modified)
     * @param {string} contentType
     * @param {number} contentId
     * @param {number|null} partNumber
     * @returns {object|null}
     */
    getFileInfo(contentType, contentId, partNumber = null) {
        const filePath = this.getFilePath(contentType, contentId, partNumber);
        if (!filePath) return null;

        try {
            const stats = fs.statSync(filePath);
            return {
                path: filePath,
                size: stats.size,
                sizeFormatted: this.formatBytes(stats.size),
                modified: stats.mtime,
                exists: true
            };
        } catch (error) {
            return null;
        }
    }

    /**
     * Create content folder and return path for saving
     * @param {string} contentType
     * @param {number} contentId
     * @returns {string} Folder path
     */
    ensureContentFolder(contentType, contentId) {
        const typeFolder = contentType === 'movie' ? 'movies' : 'episodes';
        const contentFolder = path.join(this.basePath, typeFolder, String(contentId));

        if (!fs.existsSync(contentFolder)) {
            fs.mkdirSync(contentFolder, { recursive: true });
            console.log(`📁 Created content folder: ${contentFolder}`);
        }

        return contentFolder;
    }

    /**
     * Get save path for a file
     * @param {string} contentType
     * @param {number} contentId
     * @param {number|null} partNumber
     * @param {string} extension
     * @returns {string}
     */
    getSavePath(contentType, contentId, partNumber = null, extension = '.mp4') {
        const folder = this.ensureContentFolder(contentType, contentId);
        const fileName = partNumber ? `part_${partNumber}${extension}` : `full${extension}`;
        return path.join(folder, fileName);
    }

    /**
     * Save a buffer to local file
     * @param {string} contentType
     * @param {number} contentId
     * @param {number|null} partNumber
     * @param {Buffer} buffer
     * @param {string} extension
     * @returns {string} Saved file path
     */
    async saveFile(contentType, contentId, partNumber, buffer, extension = '.mp4') {
        const savePath = this.getSavePath(contentType, contentId, partNumber, extension);

        await fs.promises.writeFile(savePath, buffer);
        console.log(`💾 Saved file: ${savePath} (${this.formatBytes(buffer.length)})`);

        return savePath;
    }

    /**
     * Read file as buffer
     * @param {string} contentType
     * @param {number} contentId
     * @param {number|null} partNumber
     * @returns {Buffer|null}
     */
    async readFile(contentType, contentId, partNumber = null) {
        const filePath = this.getFilePath(contentType, contentId, partNumber);
        if (!filePath) return null;

        try {
            return await fs.promises.readFile(filePath);
        } catch (error) {
            console.error(`❌ Error reading file: ${error.message}`);
            return null;
        }
    }

    /**
     * Get total disk usage of bot-media folder
     * @returns {number} Total size in MB
     */
    async getTotalDiskUsage() {
        try {
            const totalSize = await this.getFolderSize(this.basePath);
            return Math.round(totalSize / (1024 * 1024));
        } catch (error) {
            return 0;
        }
    }

    /**
     * Recursively get folder size
     */
    async getFolderSize(folderPath) {
        let totalSize = 0;

        try {
            const items = await fs.promises.readdir(folderPath, { withFileTypes: true });

            for (const item of items) {
                const itemPath = path.join(folderPath, item.name);

                if (item.isDirectory()) {
                    totalSize += await this.getFolderSize(itemPath);
                } else {
                    const stats = await fs.promises.stat(itemPath);
                    totalSize += stats.size;
                }
            }
        } catch (error) {
            // Ignore errors for inaccessible files
        }

        return totalSize;
    }

    /**
     * List all local files
     * @returns {Array}
     */
    async listAllFiles() {
        const files = [];

        for (const type of ['movies', 'episodes']) {
            const typeFolder = path.join(this.basePath, type);
            if (!fs.existsSync(typeFolder)) continue;

            const contentFolders = await fs.promises.readdir(typeFolder);

            for (const idFolder of contentFolders) {
                const contentPath = path.join(typeFolder, idFolder);
                const stat = await fs.promises.stat(contentPath);

                if (stat.isDirectory()) {
                    const contentFiles = await fs.promises.readdir(contentPath);

                    for (const file of contentFiles) {
                        const filePath = path.join(contentPath, file);
                        const fileStat = await fs.promises.stat(filePath);

                        files.push({
                            type: type === 'movies' ? 'movie' : 'episode',
                            contentId: parseInt(idFolder),
                            fileName: file,
                            path: filePath,
                            size: fileStat.size,
                            sizeFormatted: this.formatBytes(fileStat.size),
                            partNumber: this.extractPartNumber(file)
                        });
                    }
                }
            }
        }

        return files;
    }

    /**
     * Extract part number from filename
     */
    extractPartNumber(fileName) {
        const match = fileName.match(/part_(\d+)/);
        return match ? parseInt(match[1]) : null;
    }

    /**
     * Format bytes to human readable
     */
    formatBytes(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    /**
     * Delete local file
     */
    async deleteFile(contentType, contentId, partNumber = null) {
        const filePath = this.getFilePath(contentType, contentId, partNumber);
        if (filePath && fs.existsSync(filePath)) {
            await fs.promises.unlink(filePath);
            console.log(`🗑️ Deleted file: ${filePath}`);
            return true;
        }
        return false;
    }
}

module.exports = FileManager;
