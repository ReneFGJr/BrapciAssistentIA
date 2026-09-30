<?php
$personId = (string) old('person_id', $note['person_id'] ?? '', false);
$meetingAt = old('meeting_at', empty($note['meeting_at']) ? '' : date('Y-m-d\TH:i', strtotime($note['meeting_at'])), false);
?>
<label for="<?= esc($prefix) ?>-person">Person</label>
<select id="<?= esc($prefix) ?>-person" name="person_id" class="form-select" data-notepad-person>
    <option value="">Sem Person associada</option>
    <?php foreach ($persons as $person): ?>
        <option value="<?= (int) $person['id'] ?>" <?= $personId === (string) $person['id'] ? 'selected' : '' ?>><?= esc($person['full_name'] . ' (' . $person['nickname'] . ') — #' . $person['id']) ?></option>
    <?php endforeach; ?>
    <?php if ($personId !== '' && !in_array($personId, array_column($persons, 'id'))): ?>
        <option value="<?= esc($personId, 'attr') ?>" selected>Person indisponível</option>
    <?php endif; ?>
</select>
<label for="<?= esc($prefix) ?>-meeting">Data da reunião (horário)</label>
<input id="<?= esc($prefix) ?>-meeting" name="meeting_at" type="datetime-local" value="<?= esc($meetingAt, 'attr') ?>">
