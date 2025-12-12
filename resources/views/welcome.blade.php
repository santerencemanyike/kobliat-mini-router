<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kobliat Mini Router</title>
    <link rel="icon" href="/logo/kobliat.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>body{padding:20px}</style>
  </head>
  <body>
    <div class="container">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="d-flex align-items-center">
          <img src="/logo/kobliat.png" alt="Kobliat" class="site-logo me-2" />
          <h1 class="header-title mb-0">Kobliat Mini Router</h1>
        </div>
        <div></div>
      </div>

      <div class="card mb-4">
        <div class="card-body">
          <table id="conversationsTable" class="table table-striped" style="width:100%">
            <thead>
              <tr>
                <th>ID</th>
                <th>Customer</th>
                <th>Last Message</th>
                <th>Status</th>
                <th>Messages</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      </div>

      <!-- Conversation modal -->
      <div class="modal fade" id="conversationModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
          <div class="modal-content">
            <div class="modal-header" style="background-color: rgb(207, 46, 46); color: white;">
              <h5 class="modal-title" id="convTitle">Conversation</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
              <div class="row">
                <div class="col-md-4">
                  <div class="mb-2"><strong>Customer:</strong> <span id="convCustomer"></span></div>
                  <div class="mb-2"><strong>Status:</strong> <span id="convStatus"></span></div>
                  <div class="mb-2"><button class="btn btn-sm btn-secondary" id="refreshMessages">Refresh</button></div>
                  <hr>
                  <h6>Reply</h6>
                  <form id="replyForm">
                    <div class="mb-2 d-flex align-items-center">
                      <select id="channelSelect" name="channel" class="form-select" style="max-width:220px;margin-right:8px">
                        <option value="facebook">Facebook</option>
                        <option value="twitter">Twitter</option>
                        <option value="whatsapp">WhatsApp</option>
                        <option value="email">Email</option>
                        <option value="webchat">Webchat</option>
                      </select>
                      <img id="channelSelectIcon" src="/icons/facebook.png" class="channel-icon" alt="channel" />
                    </div>
                    <div class="mb-2">
                      <textarea name="body" class="form-control" rows="4" placeholder="Your message"></textarea>
                    </div>
                    <div class="d-grid">
                      <button class="btn btn-primary" type="submit">Send Reply (store outbound)</button>
                    </div>
                    <div class="d-grid mt-2">
                      <button id="closeConvBtn" type="button" class="btn close-conv-btn">Close Conversation</button>
                    </div>
                  </form>
                </div>
                <div class="col-md-8">
                  <ul class="nav nav-tabs" id="channelTabs" role="tablist"></ul>
                  <div class="tab-content pt-3" id="channelTabsContent"></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>

    <script>
      $(function(){
        // helper: render a small icon for known channels
        function channelIcon(ch){
          if(!ch) return '';
          const safe = (ch+'').toLowerCase().replace(/[^a-z0-9_-]/g,'');
          const src = `/icons/${safe}.png`;
          // if the icon is missing, replace the <img> with the plain channel name text
          const enc = encodeURIComponent(ch+'');
          return `<img src="${src}" alt="${safe}" class="channel-icon" onerror="this.outerHTML=decodeURIComponent('${enc}')"/>`;
        }

        // small styling for channel icons (size + spacing)
        const style = document.createElement('style');
        style.innerHTML = '.channel-icon{display:inline-block;width:20px;height:20px;object-fit:contain;margin-right:8px;vertical-align:middle}';
        document.head.appendChild(style);

        // site header logo styling and responsive button text/icon
        const headerStyle = document.createElement('style');
        headerStyle.innerHTML = `
          .site-logo{width:36px;height:36px;object-fit:contain}
          .header-title{font-size:1.25rem}
          .open-eye{display:none;vertical-align:middle;margin-left:6px}
          .open-text{display:inline}
          .delete-bin{display:none;vertical-align:middle;margin-left:6px}
          .delete-text{display:inline}
          .close-conv-btn{background-color: rgb(207,46,46); color: #fff; border-color: rgb(207,46,46)}
          @media (max-width:1000px){
            .open-text{display:none}
            .open-eye{display:inline-block}
            .delete-text{display:none}
            .delete-bin{display:inline-block}
          }
        `;
        document.head.appendChild(headerStyle);

        // update the select-adjacent icon when the channel select changes
        const $channelSelect = $('#channelSelect');
        const $channelSelectIcon = $('#channelSelectIcon');
        function updateChannelSelectIcon(){
          const val = ($channelSelect.val()||'').toLowerCase().replace(/[^a-z0-9_-]/g,'');
          const src = `/icons/${val}.png`;
          // if the image fails to load, show the channel name instead
          const enc = encodeURIComponent($channelSelect.val()||'');
          $channelSelectIcon.attr('src', src).attr('alt', val).attr('onerror', `this.outerHTML=decodeURIComponent('${enc}')`);
        }
        if($channelSelect.length && $channelSelectIcon.length){
          $channelSelect.on('change', updateChannelSelectIcon);
          updateChannelSelectIcon();
        }

        const table = $('#conversationsTable').DataTable({
          processing: true,
          serverSide: true,
          ajax: { url: '/api/conversations', type: 'GET' },
          columns: [
            { data: 'id' },
            { data: 'customer.external_id', render: function(d,t,r){ return (r.customer && r.customer.external_id) ? r.customer.external_id : ''; } },
            { data: 'last_message.body', defaultContent: '' },
            { data: 'status' },
            { data: 'messages_count' },
            { data: null, orderable:false, render: function(d){
                const custId = d.customer && d.customer.id ? d.customer.id : '';
                const eyeSvg = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" class="bi bi-eye">\n  <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8z"/>\n  <path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5z"/>\n</svg>';
                const openBtn = `<button class="btn btn-sm btn-primary openConv" data-id="${d.id}"><span class="open-text">Open</span><span class="open-eye" aria-hidden>${eyeSvg}</span></button>`;
                const binSvg = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" class="bi bi-trash">\n  <path d="M5.5 5.5A.5.5 0 0 1 6 5h4a.5.5 0 0 1 .5.5v7a.5.5 0 0 1-.5.5H6a.5.5 0 0 1-.5-.5v-7z"/>\n  <path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 1 1 0-2H5.5L6 1h4l.5 1H13.5a1 1 0 0 1 1 1z"/>\n</svg>';
                const deleteBtn = custId ? `<button class="btn btn-sm btn-danger deleteCustomer" data-customer-id="${custId}"><span class="delete-text">Delete Customer</span><span class="delete-bin" aria-hidden>${binSvg}</span></button>` : '';
                return openBtn + ' ' + deleteBtn;
              } }
          ]
        });

        let currentConvId = null;

        $('#conversationsTable tbody').on('click', 'button.openConv', function(){
          const id = $(this).data('id');
          openConversation(id);
        });

        function openConversation(id){
          currentConvId = id;
          $('#convTitle').text('Conversation ' + id);
          loadConversation(id);
          var modal = new bootstrap.Modal(document.getElementById('conversationModal'));
          modal.show();
        }

        function loadConversation(id){
          // disable actions while loading
          setButtonsDisabled(true);
          fetch('/api/conversations/' + id)
            .then(r => r.json())
            .then(payload => {
              console.log('loadConversation payload', payload);
              const data = payload.data || payload;
              $('#convCustomer').text((data.customer && data.customer.external_id) ? data.customer.external_id : '');
              $('#convStatus').text(data.status || '');
              const messages = data.messages || [];
              renderTabs(messages);
              // disable buttons if status is closed, otherwise enable
              const isClosed = (data.status||'').toString().toLowerCase() === 'closed';
              setButtonsDisabled(isClosed);
            }).catch(err => {
              console.error('loadConversation error', err);
              Swal.fire({ icon: 'error', title: 'Load failed', text: 'Failed to load conversation' });
            });
        }

        function setButtonsDisabled(disabled){
          // reply submit and textarea
          $('#replyForm').find('button[type=submit]').prop('disabled', disabled);
          $('#replyForm').find('textarea[name=body]').prop('disabled', disabled);
          // close button
          $('#closeConvBtn').prop('disabled', disabled);
          if(disabled){
            $('#closeConvBtn').addClass('disabled');
          } else {
            $('#closeConvBtn').removeClass('disabled');
          }
        }

        function renderTabs(messages){
          $('#channelTabs').empty();
          $('#channelTabsContent').empty();

          // ensure messages sorted chronologically
          messages.sort((a,b)=> new Date(a.sent_at) - new Date(b.sent_at));

          // build set of channels
          const channels = Array.from(new Set(messages.map(m => (m.channel || 'unknown').toString()))).filter(Boolean);

          // All tab
          $('#channelTabs').append('<li class="nav-item"><a class="nav-link active" id="tab-all" data-bs-toggle="tab" href="#tab-all-pane">All</a></li>');
          $('#channelTabsContent').append('<div class="tab-pane active" id="tab-all-pane"><div id="allMessages" style="max-height:500px;overflow:auto"></div></div>');
          const $all = $('#allMessages').empty();
          messages.forEach(m => {
            $all.append(`<div class="mb-2">${channelIcon(m.channel)} <strong>${m.direction||''}</strong>: ${m.body||''}<div class="text-muted small">${m.sent_at||''}</div></div><hr>`);
          });

          // Channel-specific tabs
          channels.forEach((ch, idx) => {
            const safe = ch.replace(/[^a-zA-Z0-9_-]/g, '_');
            const tabId = `tab-${safe}`;
            const paneId = `${tabId}-pane`;
            const isActive = false; // 'All' remains the default active tab
            $('#channelTabs').append(`<li class="nav-item" role="presentation"><a class="nav-link ${isActive ? 'active':''}" id="${tabId}-tab" data-bs-toggle="tab" href="#${paneId}" role="tab" aria-controls="${paneId}" aria-selected="false">${ch}</a></li>`);
            $('#channelTabsContent').append(`<div class="tab-pane ${isActive ? 'active':''}" id="${paneId}" role="tabpanel" aria-labelledby="${tabId}-tab"><div id="${paneId}-messages" style="max-height:500px;overflow:auto"></div></div>`);
            const $pane = $(`#${paneId}-messages`);
            const filtered = messages.filter(m => ((m.channel||'')+'') === ch);
            filtered.forEach(m => {
              $pane.append(`<div class="mb-2">${channelIcon(m.channel)} <strong>${m.direction||''}</strong>: ${m.body||''}<div class="text-muted small">${m.sent_at||''}</div></div><hr>`);
            });
          });
        }

        $('#replyForm').on('submit', function(e){
          e.preventDefault();
          const body = $(this).find('[name=body]').val();
          const channel = $(this).find('[name=channel]').val();
          if(!currentConvId) return Swal.fire({ icon: 'warning', title: 'No conversation', text: 'No conversation selected' });
          // client-side validation: require a non-empty message body
          const trimmed = (body || '').toString().trim();
          if(!trimmed){
            Swal.fire({ icon: 'warning', title: 'Message required', text: 'Please enter a message to send.' });
            $(this).find('[name=body]').focus();
            return;
          }
          const $btn = $(this).find('button[type=submit]');
          $btn.prop('disabled', true).text('Sending...');
          // Use UI-friendly server endpoint that doesn't require client token
          fetch('/api/conversations/' + currentConvId + '/reply-ui', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ body, channel })
          }).then(r=>{
            if(!r.ok) return r.json().then(j=>{ throw j; });
            return r.json();
          }).then(()=>{
            $btn.prop('disabled', false).text('Send Reply (store outbound)');
            table.ajax.reload(null,false);
            loadConversation(currentConvId);
            Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Reply saved', showConfirmButton: false, timer: 2000 });
          }).catch(err=>{
            $btn.prop('disabled', false).text('Send Reply (store outbound)');
            console.error(err);
            // Prefer validation error messages from the server
            let msg = 'Error sending reply';
            if(err && err.errors){
              if(err.errors.body && err.errors.body.length) msg = err.errors.body[0];
              else if(err.errors.channel && err.errors.channel.length) msg = err.errors.channel[0];
            } else if(err && err.message){
              msg = err.message;
            }
            Swal.fire({ icon: 'error', title: 'Send failed', text: msg });
          });
        });

        // Close conversation handler
        $('#closeConvBtn').on('click', function(){
          if(!currentConvId) return Swal.fire({ icon: 'warning', title: 'No conversation', text: 'No conversation selected' });
          Swal.fire({
            title: 'Close conversation?',
            text: 'Mark this conversation as closed?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, close',
            cancelButtonText: 'Cancel'
          }).then(result => {
            if(!result.isConfirmed) return;
            fetch('/api/conversations/' + currentConvId + '/close', { method: 'POST' })
              .then(r => { if(!r.ok) return r.json().then(j=>{ throw j; }); return r.json(); })
              .then(json => {
                $('#convStatus').text('closed');
                // disable reply/close after closing
                setButtonsDisabled(true);
                table.ajax.reload(null,false);
                Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Conversation closed', showConfirmButton: false, timer: 1500 });
              }).catch(err => {
                console.error('closeConversation', err);
                const msg = (err && err.message) ? err.message : 'Failed to close conversation';
                Swal.fire({ icon: 'error', title: 'Close failed', text: msg });
              });
          });
        });

        $('#refreshMessages').on('click', function(){ if(currentConvId) loadConversation(currentConvId); });

        // Delete customer handler
        $('#conversationsTable tbody').on('click', 'button.deleteCustomer', function(){
          const custId = $(this).data('customer-id');
          if(!custId) return;
          Swal.fire({
            title: 'Delete customer? ',
            text: 'Delete customer and all related conversations/messages?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete',
            cancelButtonText: 'Cancel'
          }).then(result => {
            if(!result.isConfirmed) return;
            fetch('/api/customers/' + custId, { method: 'DELETE' })
              .then(r => {
                if(r.status === 204) {
                  table.ajax.reload(null,false);
                  if(currentConvId) {
                    const modalEl = document.getElementById('conversationModal');
                    const modalInst = bootstrap.Modal.getInstance(modalEl);
                    if(modalInst) modalInst.hide();
                  }
                  Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Customer deleted', showConfirmButton: false, timer: 2000 });
                } else {
                  return r.json().then(j=>{ throw j; });
                }
              }).catch(err => { console.error('deleteCustomer', err); Swal.fire({ icon: 'error', title: 'Delete failed', text: 'Failed to delete customer' }); });
          });
        });
      });
    </script>
  </body>
</html>