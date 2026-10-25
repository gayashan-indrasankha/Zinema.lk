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
    constructor(basePath) {
        if (!basePath || !path.isAbsolute(basePath)) throw new Error('Explicit absolute local media path required.');
        this.basePath = path.resolve(basePath);
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
            this.assertContained(dir);
            if (!fs.existsSync(dir)) {
                fs.mkdirSync(dir, { recursive: true });
                console.log(`📁 Created directory: ${dir}`);
            }
        });
    }

    validateIdentity(type,id,part=null,extension='.mp4') {
        const integer=value=>(typeof value==='number'||typeof value==='string') && /^[1-9][0-9]*$/.test(String(value)) && Number.isSafeInteger(Number(value)) && Number(value)<=2147483647;
        if(!['movie','episode'].includes(type)||!integer(id)||!(part===null||part===undefined||part===0||part==='0'||integer(part))||!['.mp4','.mkv','.avi','.mov','.webm'].includes(extension))throw Error('Invalid media identity');
    }

    assertContained(candidate) {
        const relative=path.relative(this.basePath,path.resolve(candidate));
        if(relative==='..'||relative.startsWith('..'+path.sep)||path.isAbsolute(relative))throw Error('Invalid media path');
        let current=this.basePath;
        for(const component of ['',...relative.split(path.sep).filter(Boolean)]) {
            if(component)current=path.join(current,component);
            if(fs.existsSync(current) && fs.lstatSync(current).isSymbolicLink())throw Error('Invalid media link');
        }
    }

    /**
     * Get file path for content
     * @param {string} contentType - 'movie' or 'episode'
     * @param {number} contentId - Content ID from database
     * @param {number|null} partNumber - Part number (null for full file)
     * @returns {string|null} File path or null if not found
     */
    getFilePath(contentType, contentId, partNumber = null) {
        this.validateIdentity(contentType,contentId,partNumber);
        const typeFolder = contentType === 'movie' ? 'movies' : 'episodes';
        const contentFolder = path.join(this.basePath, typeFolder, String(contentId));
        this.assertContained(contentFolder);

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
            this.assertContained(filePath);if(!fs.statSync(filePath).isFile())throw Error('Invalid media file');
            return filePath;
        }

        // Try alternative extensions
        const extensions = ['.mkv', '.avi', '.mov', '.webm'];
        for (const ext of extensions) {
            const altPath = filePath.replace('.mp4', ext);
            if (fs.existsSync(altPath)) {
                this.assertContained(altPath);if(!fs.statSync(altPath).isFile())throw Error('Invalid media file');
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
        this.validateIdentity(contentType,contentId);
        const typeFolder = contentType === 'movie' ? 'movies' : 'episodes';
        const contentFolder = path.join(this.basePath, typeFolder, String(contentId));
        this.assertContained(contentFolder);

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
        this.validateIdentity(contentType,contentId,partNumber,extension);
        const folder = this.ensureContentFolder(contentType, contentId);
        const fileName = Number(partNumber)>0 ? `part_${partNumber}${extension}` : `full${extension}`;
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
        if(!Buffer.isBuffer(buffer)||buffer.length===0)throw Error('Invalid media buffer');
        const crypto=require('crypto');const expected=crypto.createHash('sha256').update(buffer).digest('hex');
        const matches=async()=>{
            this.assertContained(savePath);if(!fs.existsSync(savePath)||!fs.lstatSync(savePath).isFile()||fs.statSync(savePath).size!==buffer.length)return false;
            const hash=crypto.createHash('sha256');for await(const chunk of fs.createReadStream(savePath))hash.update(chunk);return hash.digest('hex')===expected;
        };
        if(fs.existsSync(savePath)){if(await matches())return savePath;throw Error('media_conflict');}
        const temporary=path.join(path.dirname(savePath),'.ingest-'+crypto.randomBytes(16).toString('hex'));
        try {
            const fd=await fs.promises.open(temporary,'wx',0o600);
            try{await fd.writeFile(buffer);await fd.sync();}finally{await fd.close();}
            try {await fs.promises.link(temporary,savePath);}catch(error){if(error.code!=='EEXIST')throw Error('media_publish_unavailable');if(!(await matches()))throw Error('media_conflict');}
            return savePath;
        }finally{if(fs.existsSync(temporary))await fs.promises.unlink(temporary);}
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
                if(!/^[1-9][0-9]*$/.test(idFolder)||Number(idFolder)>2147483647)continue;
                const contentPath = path.join(typeFolder, idFolder);
                const stat = await fs.promises.lstat(contentPath);

                if (stat.isDirectory()) {
                    const contentFiles = await fs.promises.readdir(contentPath);

                    for (const file of contentFiles) {
                        if(!/^(?:full|part_[1-9][0-9]*)\.(?:mp4|mkv|avi|mov|webm)$/.test(file))continue;
                        const filePath = path.join(contentPath, file);
                        const fileStat = await fs.promises.lstat(filePath);
                        if(!fileStat.isFile() || fileStat.isSymbolicLink())continue;

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
