const paths = {
    'magnifying-glass': 'M10 3a7 7 0 1 0 0 14 7 7 0 0 0 0-14M15 15l6 6',
    'cloud-arrow-down': 'M6 17a5 5 0 0 1-1-10 7 7 0 0 1 13 1 4 4 0 0 1 0 8M12 10v11m-4-4 4 4 4-4',
    'plus': 'M12 4v16M4 12h16',
    'rocket': 'M9 15 5 19l-1-5 5-5M9 15l6-6M9 15l1 5 5-5M9 9c3-5 7-6 12-6 0 5-1 9-6 12M15 6h3v3',
    'download': 'M12 3v12m-5-5 5 5 5-5M4 16v5h16v-5',
    'file-excel': 'M5 2h10l4 4v16H5zM15 2v5h4M8 11l8 7m0-7-8 7',
    'check-double': 'm2 12 4 4L16 6m-4 10 10-10',
    'award': 'M12 3a6 6 0 1 0 0 12 6 6 0 0 0 0-12M8 14l-2 8 6-3 6 3-2-8',
    'certificate': 'M3 3h18v14H3zM7 7h10M7 11h5M15 15v7l3-2 3 2v-7',
    'file-pdf': 'M5 2h10l4 4v16H5zM15 2v5h4M8 17l4-7 4 7M10 15h4',
    'book': 'M3 4h6l3 2 3-2h6v16h-6l-3 2-3-2H3zM12 6v16',
    'envelope-open-text': 'm3 9 9-7 9 7v12H3zM3 9l9 8 9-8M7 8h10M7 11h10',
    'arrow-left': 'M21 12H3m7-7-7 7 7 7',
    'upload': 'M12 16V3m-5 5 5-5 5 5M4 16v5h16v-5',
    'floppy-disk': 'M4 3h13l4 4v14H3V3zM7 3v6h10V3M7 21v-8h10v8',
    'check': 'm4 12 5 5L20 5',
    'ban': 'M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20M5 5l14 14',
    'rotate-right': 'M20 9a9 9 0 1 0 1 7M20 3v6h-6',
    'circle-check': 'M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20m-5 10 3 3 7-7'
};

export function renderIcon(tag) {
    const name = tag.match(/\bfa-(magnifying-glass|cloud-arrow-down|plus|rocket|download|file-excel|check-double|award|certificate|file-pdf|book|envelope-open-text|arrow-left|upload|floppy-disk|check|ban|rotate-right|circle-check)\b/)?.[1];
    if (!name) throw new Error(`Unsupported mockup icon: ${tag}`);
    return `<svg class="demo-icon" data-icon="${name}" viewBox="0 0 24 24" aria-hidden="true"><path d="${paths[name]}"/></svg>`;
}
