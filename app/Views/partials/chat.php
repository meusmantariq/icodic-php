<button class="chat-fab" type="button" aria-label="Open chat"><?= icon('MessageCircle') ?></button>
<div class="chat" id="chat">
  <div class="chat-head"><?= icon('Sparkles') ?><div style="flex:1"><b><?= e(t('chat.title')) ?></b><small>Software · ERP · Cyber Security · IT Services</small></div><button type="button" data-chat-close aria-label="Close" style="color:#fff"><?= icon('X') ?></button></div>
  <div class="chat-body"><div class="msg bot">Hi! I'm the iCodic AI assistant. Ask me about our software, ERP solutions, cyber security or IT services.</div></div>
  <div class="chat-sugg"><button type="button">What ERP solutions do you offer?</button><button type="button">How much does AutoCAD cost?</button><button type="button">Do you provide cyber security services in Dubai?</button><button type="button">Which software is available in Pakistan?</button></div>
  <form class="chat-form"><input type="text" placeholder="<?= e(t('chat.placeholder')) ?>" maxlength="1000" autocomplete="off"><button type="submit" aria-label="Send"><?= icon('Send') ?></button></form>
</div>
