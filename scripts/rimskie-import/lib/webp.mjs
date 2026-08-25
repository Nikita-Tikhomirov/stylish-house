import { readFile } from 'node:fs/promises';

const validWebpChunks = new Set(['VP8 ', 'VP8L', 'VP8X']);

export function detectImageFormat(bytes) {
    if (!Buffer.isBuffer(bytes)) return null;
    if (bytes.length >= 20 && bytes.subarray(0, 4).equals(Buffer.from('RIFF'))
        && bytes.subarray(8, 12).equals(Buffer.from('WEBP'))
        && bytes.readUInt32LE(4) + 8 === bytes.length
        && validWebpChunks.has(bytes.subarray(12, 16).toString('ascii'))) {
        const firstChunkSize = bytes.readUInt32LE(16);
        const paddedChunkSize = firstChunkSize + (firstChunkSize % 2);
        if (20 + paddedChunkSize <= bytes.length) return 'webp';
    }
    if (bytes.length >= 3 && bytes[0] === 0xff && bytes[1] === 0xd8 && bytes[2] === 0xff) return 'jpeg';
    if (bytes.length >= 8 && bytes.subarray(0, 8).equals(Buffer.from([
        0x89, 0x50, 0x4e, 0x47, 0x0d, 0x0a, 0x1a, 0x0a,
    ]))) return 'png';

    return null;
}

export async function validateImageFile(path, expectedFormat = null) {
    try {
        const detectedFormat = detectImageFormat(await readFile(path));
        return expectedFormat ? detectedFormat === expectedFormat : Boolean(detectedFormat);
    } catch (error) {
        if (error?.code === 'ENOENT') return false;
        throw error;
    }
}

