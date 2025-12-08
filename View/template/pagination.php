<?php
/**
 * PAGINATION COMPONENT - Premium Style
 * Merah putih theme dengan animasi smooth dan responsive design
 * 
 * Usage:
 * include Path::template('pagination.php');
 * renderPagination($page, $total_pages, $total, $items_per_page, $base_url, $search_param);
 */

function renderPagination($page, $total_pages, $total, $items_per_page, $base_url = '?action=', $search_param = '') {
    if ($total_pages <= 1) return; // Jangan tampilkan jika hanya 1 halaman
    
    $query_string = !empty($search_param) ? '&search=' . urlencode($search_param) : '';
    
    ?>
    <nav aria-label="Page navigation" class="pagination-container">
        <div class="pagination-wrapper">
            <!-- Pagination Controls -->
            <ul class="pagination-list">
                <!-- Tombol Awal -->
                <?php if ($page > 1): ?>
                    <li class="pagination-item">
                        <a href="<?= $base_url ?>&page=1<?= $query_string ?>" 
                           class="pagination-link pagination-first" 
                           title="Ke halaman pertama">
                            <i class="fas fa-chevron-left"></i><i class="fas fa-chevron-left"></i>
                        </a>
                    </li>
                <?php else: ?>
                    <li class="pagination-item disabled">
                        <span class="pagination-link pagination-first">
                            <i class="fas fa-chevron-left"></i><i class="fas fa-chevron-left"></i>
                        </span>
                    </li>
                <?php endif; ?>

                <!-- Tombol Sebelumnya -->
                <?php if ($page > 1): ?>
                    <li class="pagination-item">
                        <a href="<?= $base_url ?>&page=<?= $page - 1 ?><?= $query_string ?>" 
                           class="pagination-link pagination-prev" 
                           title="Halaman sebelumnya">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    </li>
                <?php else: ?>
                    <li class="pagination-item disabled">
                        <span class="pagination-link pagination-prev">
                            <i class="fas fa-chevron-left"></i>
                        </span>
                    </li>
                <?php endif; ?>

                <!-- Nomor Halaman -->
                <?php 
                // Calculate range to display
                $start = max(1, $page - 2);
                $end = min($total_pages, $page + 2);
                
                // Tampilkan tombol awal jika tidak dimulai dari 1
                if ($start > 1): ?>
                    <li class="pagination-item">
                        <a href="<?= $base_url ?>&page=1<?= $query_string ?>" class="pagination-link">1</a>
                    </li>
                    <?php if ($start > 2): ?>
                        <li class="pagination-item disabled">
                            <span class="pagination-link pagination-ellipsis">...</span>
                        </li>
                    <?php endif;
                endif; 
                
                // Tampilkan nomor halaman dalam range
                for ($i = $start; $i <= $end; $i++):
                    if ($i == $page): ?>
                        <li class="pagination-item active">
                            <span class="pagination-link"><?= $i ?></span>
                        </li>
                    <?php else: ?>
                        <li class="pagination-item">
                            <a href="<?= $base_url ?>&page=<?= $i ?><?= $query_string ?>" 
                               class="pagination-link">
                                <?= $i ?>
                            </a>
                        </li>
                    <?php endif;
                endfor;
                
                // Tampilkan tombol akhir jika tidak berakhir di total_pages
                if ($end < $total_pages): ?>
                    <?php if ($end < $total_pages - 1): ?>
                        <li class="pagination-item disabled">
                            <span class="pagination-link pagination-ellipsis">...</span>
                        </li>
                    <?php endif; ?>
                    <li class="pagination-item">
                        <a href="<?= $base_url ?>&page=<?= $total_pages ?><?= $query_string ?>" 
                           class="pagination-link">
                            <?= $total_pages ?>
                        </a>
                    </li>
                <?php endif; ?>

                <!-- Tombol Selanjutnya -->
                <?php if ($page < $total_pages): ?>
                    <li class="pagination-item">
                        <a href="<?= $base_url ?>&page=<?= $page + 1 ?><?= $query_string ?>" 
                           class="pagination-link pagination-next" 
                           title="Halaman selanjutnya">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </li>
                <?php else: ?>
                    <li class="pagination-item disabled">
                        <span class="pagination-link pagination-next">
                            <i class="fas fa-chevron-right"></i>
                        </span>
                    </li>
                <?php endif; ?>

                <!-- Tombol Akhir -->
                <?php if ($page < $total_pages): ?>
                    <li class="pagination-item">
                        <a href="<?= $base_url ?>&page=<?= $total_pages ?><?= $query_string ?>" 
                           class="pagination-link pagination-last" 
                           title="Ke halaman terakhir">
                            <i class="fas fa-chevron-right"></i><i class="fas fa-chevron-right"></i>
                        </a>
                    </li>
                <?php else: ?>
                    <li class="pagination-item disabled">
                        <span class="pagination-link pagination-last">
                            <i class="fas fa-chevron-right"></i><i class="fas fa-chevron-right"></i>
                        </span>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <style>
        /* ====== PAGINATION MODERN PREMIUM STYLE ====== */
        .pagination-container {
            margin: 2.5rem 0;
            padding: 1.5rem;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(255, 255, 255, 0.98) 100%);
            border-radius: 12px;
            backdrop-filter: blur(10px);
            animation: fadeInUp 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
            border: 1px solid rgba(198, 40, 40, 0.1);
            box-shadow: 0 2px 12px rgba(198, 40, 40, 0.08);
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(15px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .pagination-wrapper {
            display: flex;
            flex-direction: column;
            gap: 0;
            align-items: center;
            justify-content: center;
        }

        /* Pagination List */
        .pagination-list {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            gap: 0.6rem;
            flex-wrap: wrap;
            justify-content: center;
            align-items: center;
        }

        /* Pagination Item */
        .pagination-item {
            display: inline-flex;
        }

        /* Pagination Link */
        .pagination-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 42px;
            height: 42px;
            padding: 0 0.85rem;
            border-radius: 8px;
            border: 1.5px solid #e8e8e8;
            background: #ffffff;
            color: #333333;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            position: relative;
            text-decoration: none;
            user-select: none;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }

        .pagination-link::before {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: 8px;
            background: linear-gradient(135deg, rgba(198, 40, 40, 0.08) 0%, rgba(198, 40, 40, 0) 100%);
            opacity: 0;
            transition: opacity 0.3s ease;
            pointer-events: none;
        }

        .pagination-link::after {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: 8px;
            background: radial-gradient(circle, rgba(198, 40, 40, 0.15) 0%, transparent 70%);
            opacity: 0;
            transform: scale(0);
            transition: all 0.5s ease;
            pointer-events: none;
        }

        /* Hover State */
        .pagination-link:not(.pagination-ellipsis):hover {
            border-color: #c62828;
            background: linear-gradient(135deg, #fff9f9 0%, #ffffff 100%);
            color: #c62828;
            box-shadow: 0 6px 16px rgba(198, 40, 40, 0.18);
            transform: translateY(-3px);
        }

        .pagination-link:hover::before {
            opacity: 1;
        }

        .pagination-link:active::after {
            animation: ripple 0.6s ease-out;
        }

        @keyframes ripple {
            0% {
                transform: scale(0);
                opacity: 0.8;
            }
            100% {
                transform: scale(2.5);
                opacity: 0;
            }
        }

        /* Active State */
        .pagination-item.active .pagination-link {
            background: linear-gradient(135deg, #c62828 0%, #a01c1c 100%);
            border-color: #c62828;
            color: #ffffff;
            box-shadow: 0 8px 20px rgba(198, 40, 40, 0.35), 0 0 0 4px rgba(198, 40, 40, 0.1);
            transform: scale(1.08);
            font-weight: 700;
            position: relative;
        }

        .pagination-item.active .pagination-link::before {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.15) 0%, rgba(255, 255, 255, 0) 100%);
            opacity: 1;
        }

        .pagination-item.active .pagination-link:hover {
            box-shadow: 0 10px 28px rgba(198, 40, 40, 0.45), 0 0 0 4px rgba(198, 40, 40, 0.12);
            transform: scale(1.1);
        }

        /* Disabled State */
        .pagination-item.disabled .pagination-link {
            opacity: 0.35;
            cursor: not-allowed;
            border-color: #f5f5f5;
            background: #f9f9f9;
            box-shadow: none;
            color: #b0b0b0;
        }

        .pagination-item.disabled .pagination-link:hover {
            transform: none;
            border-color: #f5f5f5;
            background: #f9f9f9;
            box-shadow: none;
            color: #b0b0b0;
        }

        /* Ellipsis */
        .pagination-ellipsis {
            cursor: default !important;
            border: none !important;
            background: transparent !important;
            color: #c0c0c0 !important;
            box-shadow: none !important;
            padding: 0 0.25rem !important;
            font-weight: 700;
        }

        .pagination-ellipsis:hover {
            transform: none !important;
            background: transparent !important;
            border: none !important;
            color: #c0c0c0 !important;
        }

        /* Icon Buttons */
        .pagination-first,
        .pagination-last,
        .pagination-prev,
        .pagination-next {
            padding: 0;
            min-width: 42px;
        }

        .pagination-first i,
        .pagination-last i,
        .pagination-prev i,
        .pagination-next i {
            font-size: 0.85rem;
            display: flex;
            gap: 3px;
            align-items: center;
        }

        /* Mobile Responsiveness */
        @media (max-width: 768px) {
            .pagination-container {
                padding: 1.2rem 1rem;
                margin: 2rem 0;
            }

            .pagination-link {
                min-width: 38px;
                height: 38px;
                padding: 0 0.7rem;
                font-size: 0.9rem;
                border-radius: 6px;
            }

            .pagination-list {
                gap: 0.5rem;
            }

            .pagination-item.active .pagination-link {
                transform: scale(1.05);
            }
        }

        @media (max-width: 480px) {
            .pagination-container {
                padding: 1rem;
                margin: 1.5rem 0;
                border-radius: 10px;
            }

            .pagination-link {
                min-width: 36px;
                height: 36px;
                padding: 0 0.5rem;
                font-size: 0.85rem;
                border-radius: 5px;
                border-width: 1px;
            }

            .pagination-first,
            .pagination-last {
                display: none;
            }

            .pagination-list {
                gap: 0.4rem;
            }

            .pagination-item.active .pagination-link {
                transform: scale(1.03);
            }
        }

        /* Accessibility - Focus State */
        .pagination-link:focus-visible {
            outline: 2px solid #c62828;
            outline-offset: 3px;
            border-radius: 8px;
        }

        /* Page number animation on load */
        .pagination-item {
            animation: slideIn 0.4s ease-out;
            animation-fill-mode: both;
        }

        .pagination-item:nth-child(1) { animation-delay: 0s; }
        .pagination-item:nth-child(2) { animation-delay: 0.05s; }
        .pagination-item:nth-child(3) { animation-delay: 0.1s; }
        .pagination-item:nth-child(4) { animation-delay: 0.15s; }
        .pagination-item:nth-child(5) { animation-delay: 0.2s; }
        .pagination-item:nth-child(6) { animation-delay: 0.25s; }
        .pagination-item:nth-child(7) { animation-delay: 0.3s; }
        .pagination-item:nth-child(8) { animation-delay: 0.35s; }
        .pagination-item:nth-child(9) { animation-delay: 0.4s; }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(8px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Active link pulse animation */
        .pagination-item.active {
            animation: activePulse 0.6s ease-out;
        }

        @keyframes activePulse {
            0% {
                transform: scale(0.92);
                filter: brightness(1.1);
            }
            50% {
                transform: scale(1.12);
            }
            100% {
                transform: scale(1.08);
                filter: brightness(1);
            }
        }

        /* Smooth transition for page changes */
        .pagination-container {
            will-change: contents;
        }
    </style>
    <?php
}
?>
