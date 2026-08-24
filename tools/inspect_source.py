import os
import re

api_dir = 'wp-content/plugins/apexseo/src/API'
all_routes = []

for root, _, files in os.walk(api_dir):
    for f in sorted(files):
        if f.endswith('.php') and f != 'AbstractRestController.php':
            filepath = os.path.join(root, f)
            with open(filepath, 'r', encoding='utf-8') as fp:
                code = fp.read()
            
            # Find registerRoute or register_rest_route calls
            matches = re.finditer(r"(?:register_rest_route\(\s*self::NAMESPACE|\$this->registerRoute)\s*\(\s*['\"]([^'\"]+)['\"]\s*,\s*(\[[^;]+\])\s*\);", code, re.DOTALL)
            for m in matches:
                route_path = m.group(1)
                config = m.group(2)
                
                # Check for array of endpoint configs or single endpoint config
                methods_matches = re.findall(r"['\"]methods['\"]\s*=>\s*([^,\n]+)", config)
                callback_matches = re.findall(r"['\"]callback['\"]\s*=>\s*\[([^\]]+)\]", config)
                perm_matches = re.findall(r"['\"]permission_callback['\"]\s*=>\s*\[([^\]]+)\]", config)
                
                for idx in range(max(len(methods_matches), 1)):
                    method = methods_matches[idx].strip().strip('\'"') if idx < len(methods_matches) else 'UNKNOWN'
                    cb = callback_matches[idx].strip() if idx < len(callback_matches) else 'UNKNOWN'
                    perm = perm_matches[idx].strip() if idx < len(perm_matches) else 'UNKNOWN'
                    
                    full_route = '/apexseo/v1/' + route_path.lstrip('/')
                    all_routes.append({
                        'file': f,
                        'route_pattern': route_path,
                        'full_route': full_route,
                        'method': method,
                        'callback': cb,
                        'permission': perm
                    })

print(f'Total endpoints found: {len(all_routes)}')
for idx, r in enumerate(all_routes, 1):
    m = r['method']
    fr = r['full_route']
    fl = r['file']
    cb = r['callback']
    perm = r['permission']
    print(f"{idx:2d}. {m:6s} {fr:35s} in {fl:30s} -> cb: {cb} | perm: {perm}")
