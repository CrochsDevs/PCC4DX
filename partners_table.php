<?php
if(empty($partners)): ?>
    <tr>
        <td colspan="6" class="text-center">No partners found. Add your first partner!</td>
    </tr>
<?php else: ?>
    <?php foreach ($partners as $partner): ?>
        <tr class="clickable-row" data-href="select.php?partner_id=<?= $partner['id'] ?>">
            <!-- Same table row content from original code -->
        </tr>
    <?php endforeach; ?>
<?php endif; ?>