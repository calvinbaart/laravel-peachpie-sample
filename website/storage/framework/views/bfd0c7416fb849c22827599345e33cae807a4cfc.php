<?php if($paginator->hasPages()): ?>
    <ul class="pagination" role="navigation">
        
        <?php if($paginator->onFirstPage()): ?>
            <li class="disabled" aria-disabled="true"><span><?php echo app('translator')->getFromJson('pagination.previous'); ?></span></li>
        <?php else: ?>
            <li><a href="<?php echo e($paginator->previousPageUrl()); ?>" rel="prev"><?php echo app('translator')->getFromJson('pagination.previous'); ?></a></li>
        <?php endif; ?>

        
        <?php if($paginator->hasMorePages()): ?>
            <li><a href="<?php echo e($paginator->nextPageUrl()); ?>" rel="next"><?php echo app('translator')->getFromJson('pagination.next'); ?></a></li>
        <?php else: ?>
            <li class="disabled" aria-disabled="true"><span><?php echo app('translator')->getFromJson('pagination.next'); ?></span></li>
        <?php endif; ?>
    </ul>
<?php endif; ?>

<?php /* /mnt/c/Development/laravel-peachpie-sample/website/resources/views/vendor/pagination/simple-default.blade.php */ ?>