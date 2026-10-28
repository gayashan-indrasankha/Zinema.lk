'use strict';
const fs=require('node:fs'),path=require('node:path');
// Local acceptance ceiling only; not a claim about any provider's attachment limit.
const MAX_MEDIA_BYTES=2147483648;
const MIME={'.mp4':'video/mp4','.mkv':'video/x-matroska','.avi':'video/x-msvideo','.mov':'video/quicktime','.webm':'video/webm'};
function inspectMedia(file) {
    if(typeof file!=='string'||!path.isAbsolute(file)||!MIME[path.extname(file).toLowerCase()])throw Error('media_source_unavailable');
    const stat=fs.lstatSync(file);
    if(!stat.isFile()||stat.isSymbolicLink()||stat.nlink!==1||!Number.isSafeInteger(stat.size)||stat.size<1||stat.size>MAX_MEDIA_BYTES)throw Error('media_source_unavailable');
    return {bytes:stat.size,mimetype:MIME[path.extname(file).toLowerCase()]};
}
module.exports={MAX_MEDIA_BYTES,inspectMedia};
