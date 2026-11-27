const t = (s) => (typeof window.__ === 'function' ? window.__(s) : s);

window.HomeworkPacket = {
  bind(form) {
    if (!form) return;

    const button = form.querySelector('button');
    const status = form.querySelector('[data-print-status]');
    const headers = {'Accept': 'application/json'};
    let jobUrl;

    async function post(url, message) {
      const response = await fetch(url, {method: 'POST', body: new FormData(form), headers});
      if (!response.ok) {
        const failure = await response.json().catch(() => ({}));
        throw new Error(failure.message || message);
      }
    }

    async function prepare() {
      if (!form.dataset.generate) return;

      status.textContent = t('Generating worksheets…');
      await post(form.dataset.generate, t('Couldn’t generate the worksheets.'));
      delete form.dataset.generate;
      status.textContent = '';
    }

    async function print() {
      status.textContent = t('Sending to the Mac Studio…');
      jobUrl ??= form.action.replace(/[^/]+$/, crypto.randomUUID());
      await post(jobUrl, t('Couldn’t send the packet to the Mac Studio.'));

      for (let check = 0; check < 20 && form.isConnected; check++) {
        const response = await fetch(jobUrl, {cache: 'no-store', headers});
        if (!response.ok) throw new Error(t('Couldn’t check the Mac Studio print status.'));

        const {state} = await response.json();
        if (state === 'sent') {
          status.textContent = t('Sent to the printer by the Mac Studio.');
          jobUrl = undefined;
          return;
        }
        if (state === 'failed') {
          jobUrl = undefined;
          throw new Error(t('The Mac Studio couldn’t send the packet to the printer.'));
        }
        await new Promise(resolve => setTimeout(resolve, 2000));
      }
      status.textContent = t('Still waiting for the Mac Studio.');
    }

    form.addEventListener('submit', async (event) => {
      event.preventDefault();
      if (button.disabled) return;

      button.disabled = true;
      try {
        await prepare();
        await print();
      } catch (error) {
        status.textContent = error.message;
      } finally {
        button.disabled = false;
      }
    });

    if (form.dataset.generate) {
      prepare()
        .catch(() => { status.textContent = t('Couldn’t generate worksheets. Tap Print all to try again.'); })
        .finally(() => { button.disabled = false; });
    }
  }
};
