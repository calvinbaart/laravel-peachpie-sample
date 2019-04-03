<?php if($paginator->hasPages()): ?>
    <ul class="pagination" role="navigation">
        
        <?php if($paginator->onFirstPage()): ?>
            <li class="disabled" aria-disabled="true" aria-label="<?php echo app('translator')->getFromJson('pagination.previous'); ?>">
                <span aria-hidden="true">&lsaquo;</span>
            </li>
        <?php else: ?>
            <li>
                <a href="<?php echo e($paginator->previousPageUrl()); ?>" rel="prev" aria-label="<?php echo app('translator')->getFromJson('pagination.previous'); ?>">&lsaquo;</a>
            </li>
        <?php endif; ?>

        
        <?php $__currentLoopData = $elements; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $element): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            
            <?php if(is_string($element)): ?>
                <li class="disabled" aria-disabled="true"><span><?php echo e($element); ?></span></li>
            <?php endif; ?>

            
            <?php if(is_array($element)): ?>
                <?php $__currentLoopData = $element; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $page => $url): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php if($page == $paginator->currentPage()): ?>
                        <li class="active" aria-current="page"><span><?php echo e($page); ?></span></li>
                    <?php else: ?>
                        <li><a href="<?php echo e($url); ?>"><?php echo e($page); ?></a></li>
                    <?php endif; ?>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            <?php endif; ?>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

        
        <?php if($paginator->hasMorePages()): ?>
            <li>
                <a href="<?php echo e($paginator->nextPageUrl()); ?>" rel="next" aria-label="<?php echo app('translator')->getFromJson('pagination.next'); ?>">&rsaquo;</a>
            </li>
        <?php else: ?>
            <li class="disabled" aria-disabled="true" aria-label="<?php echo app('translator')->getFromJson('pagination.next'); ?>">
                <span aria-hidden="true">&rsaquo;</span>
            </li>
        <?php endif; ?>
    </ul>
<?php endif; ?>

<?php /* /mnt/c/Development/laravel-peachpie-sample/website/vendor/calvinbaart/laravel-peachpie/src/Illuminate/Pagination/resources/views/default.blade.php */ ?>