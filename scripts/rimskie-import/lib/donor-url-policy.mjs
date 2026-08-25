const donorHostname = 'rimskie.com';

const imagePathPrefixes = [
    '/images/',
    '/media/',
    '/storage/',
    '/upload/',
    '/uploads/',
];

function pathAllowed(pathname, kind) {
    if (kind === 'category') {
        return /^\/catalog\/rimskie-shtory\/[a-z0-9-]+\/?$/i.test(pathname);
    }
    if (kind === 'product') {
        return /^\/products\/\d+(?:-[a-z0-9-]+)?\/?$/i.test(pathname);
    }
    if (kind === 'html') {
        return pathAllowed(pathname, 'category') || pathAllowed(pathname, 'product');
    }
    if (kind !== 'image') return false;

    return imagePathPrefixes.some((prefix) => pathname.startsWith(prefix) && pathname.length > prefix.length);
}

function assertUnambiguousUrlText(value, label) {
    const text = String(value);
    if (text.includes('\\') || /%(?:2e|2f|5c)|%25(?:2e|2f|5c)/i.test(text)) {
        throw new Error(`${label} contains an encoded separator or ambiguous dot segment`);
    }
}

export function resolveDonorOriginUrl(value, { label = 'donor URL' } = {}) {
    let url;
    try {
        url = new URL(value);
    } catch (error) {
        throw new Error(`${label} is an invalid URL`, { cause: error });
    }

    if (url.protocol !== 'https:') {
        throw new Error(`${label} must use HTTPS`);
    }
    if (url.hostname !== donorHostname || url.username || url.password || url.port) {
        throw new Error(`${label} must use the approved rimskie.com origin`);
    }

    return url.href;
}

export function resolveDonorUrl(value, {
    baseUrl,
    kind = 'html',
    label = 'donor URL',
} = {}) {
    assertUnambiguousUrlText(value, label);
    let candidate;
    try {
        candidate = baseUrl ? new URL(value, baseUrl).href : value;
    } catch (error) {
        throw new Error(`${label} is an invalid URL`, { cause: error });
    }
    const url = new URL(resolveDonorOriginUrl(candidate, { label }));
    if (!pathAllowed(url.pathname, kind)) {
        throw new Error(`${label} is outside the approved ${kind} path boundary`);
    }

    url.hash = '';
    return url.href;
}

export function isApprovedDonorUrl(value, options) {
    try {
        resolveDonorUrl(value, options);
        return true;
    } catch {
        return false;
    }
}

export function isApprovedDonorOriginUrl(value) {
    try {
        resolveDonorOriginUrl(value);
        return true;
    } catch {
        return false;
    }
}
