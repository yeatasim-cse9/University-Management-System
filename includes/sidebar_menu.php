<?php
/**
 * Sidebar Menu Helper Functions
 * ACADEMIX - Academic Management System
 * 
 * Generates responsive sidebar navigation items that work on all screen sizes.
 * Shows icons + text labels on ALL devices (no icon-only mode).
 */

/**
 * Generate a single responsive sidebar navigation item
 * 
 * @param string $url - The URL for the link
 * @param string $icon - Font Awesome icon class (e.g., 'fa-home')
 * @param string $label - The text label for the link
 * @param bool $isActive - Whether this is the current active page
 * @param int|null $badge - Optional badge count to display
 * @return string HTML for the navigation item
 */
function sidebar_item($url, $icon, $label, $isActive = false, $badge = null) {
    if ($isActive) {
        $activeClass = 'bg-[#facc15] text-black border-2 border-black shadow-[4px_4px_0px_#000] z-10';
        $iconClass = 'text-black';
    } else {
        $activeClass = 'text-black hover:bg-slate-50 border-2 border-transparent hover:border-black transition-all';
        $iconClass = 'text-black/40 group-hover:text-black';
    }
    
    $badgeHtml = '';
    if ($badge !== null && $badge > 0) {
        $badgeValue = $badge > 99 ? '99+' : $badge;
        $badgeHtml = "<span class=\"ml-auto bg-black text-[#facc15] text-[10px] font-black px-1.5 py-0.5 border border-black\">{$badgeValue}</span>";
    }
    
    return <<<HTML
<a href="{$url}" class="group flex items-center gap-3 px-4 py-3 transition-all {$activeClass}">
    <span class="flex items-center justify-center w-5 transition-transform group-hover:scale-110 {$iconClass}">
        <i class="fas {$icon} text-[15px]"></i>
    </span>
    <span class="text-[12px] font-black tracking-widest uppercase font-mono">{$label}</span>
    {$badgeHtml}
</a>
HTML;
}

/**
 * Helper function to check if current page matches the given URL
 * 
 * @param string $url - The URL to check against
 * @return bool
 */
function is_sidebar_active($url) {
    $current_path = $_SERVER['PHP_SELF'];
    return strpos($current_path, $url) !== false;
}
