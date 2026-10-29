from pathlib import Path
import re
root = Path(__file__).resolve().parents[1]
required = ['plugin/rescate-alimentos/rescate-alimentos.php', 'plugin/rescate-alimentos/assets/app.js', 'plugin/rescate-alimentos/assets/app.css', 'theme/rescate-mobile/style.css', 'theme/rescate-mobile/index.php']
for item in required:
    assert (root / item).is_file(), f'Missing {item}'
for folder in ['plugin', 'theme']:
    for p in (root / folder).rglob('*'):
        if p.suffix in ['.php', '.js', '.css', '.json']:
            text = p.read_text()
            assert not re.search('[\U0001F300-\U0001FAFF]', text), f'Emoji in {p}'
            assert 'sk_live_' not in text and 'ghp_' not in text, f'Credential in {p}'
print('Package files, no emoji and credential markers: passed')
