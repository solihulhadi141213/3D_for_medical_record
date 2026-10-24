
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>RME Anatomy Viewer</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f1f5f9;
            color: #0f172a;
        }

        .header {
            padding: 16px 24px;
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
        }

        .header h1 {
            margin: 0 0 6px;
            font-size: 20px;
        }

        .header p {
            margin: 0;
            font-size: 13px;
            color: #64748b;
        }

        .viewer-container {
            padding: 20px;
        }

        .viewer {
            position: relative;
            width: 100%;
            height: 75vh;
            min-height: 400px;
            overflow: hidden;
            border-radius: 12px;
            background: #101827;
        }

        #anatomyCanvas {
            display: block;
            width: 100%;
            height: 100%;
        }

        .viewer-info {
            position: absolute;
            top: 16px;
            left: 16px;
            padding: 10px 14px;
            border-radius: 8px;
            background: rgba(15, 23, 42, 0.8);
            color: #ffffff;
            font-size: 13px;
            pointer-events: none;
        }

        #status {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            color: #ffffff;
            text-align: center;
            pointer-events: none;
        }

        .instructions {
            margin-top: 12px;
            font-size: 13px;
            line-height: 1.8;
            color: #475569;
        }

        @media (max-width: 600px) {
            .viewer-container {
                padding: 10px;
            }

            .viewer {
                height: 65vh;
                min-height: 350px;
            }
        }
    </style>

    <!-- Mengarahkan import Three.js ke file lokal -->
    <script type="importmap">
    {
        "imports": {
            "three": "./node_modules/three/build/three.module.js"
        }
    }
    </script>
</head>
<body>

    <header class="header">
        <h1>RME Anatomy Viewer</h1>
        <p>Visualisasi anatomi 3D — tahap pengembangan awal</p>
    </header>

    <main class="viewer-container">
        <div class="viewer" id="viewer">
            <canvas id="anatomyCanvas"></canvas>

            <div class="viewer-info">
                Model anatomi: FJ1253
            </div>

            <div id="status">Memuat model anatomi...</div>
        </div>

        <div class="instructions">
            <strong>Kontrol:</strong>
            Geser mouse untuk memutar model · Scroll untuk zoom ·
            Klik kanan dan geser untuk menggeser tampilan.
        </div>
    </main>

    <script type="module">
        import * as THREE from 'three';

        import { OrbitControls } from
            './node_modules/three/examples/jsm/controls/OrbitControls.js';

        import { GLTFLoader } from
            './node_modules/three/examples/jsm/loaders/GLTFLoader.js';

        const canvas = document.getElementById('anatomyCanvas');
        const viewer = document.getElementById('viewer');
        const status = document.getElementById('status');

        // 1. Membuat scene 3D
        const scene = new THREE.Scene();
        scene.background = new THREE.Color(0x101827);

        // 2. Kamera
        const camera = new THREE.PerspectiveCamera(
            45,
            viewer.clientWidth / viewer.clientHeight,
            0.01,
            10000
        );

        camera.position.set(0, 0, 5);

        // 3. Renderer
        const renderer = new THREE.WebGLRenderer({
            canvas: canvas,
            antialias: true,
            alpha: false
        });

        renderer.setPixelRatio(
            Math.min(window.devicePixelRatio, 2)
        );

        renderer.setSize(
            viewer.clientWidth,
            viewer.clientHeight
        );

        renderer.outputColorSpace = THREE.SRGBColorSpace;

        // 4. Pencahayaan
        scene.add(new THREE.AmbientLight(0xffffff, 2));

        const light = new THREE.DirectionalLight(0xffffff, 3);
        light.position.set(5, 10, 7);
        scene.add(light);

        // 5. Kontrol rotasi dan zoom
        const controls = new OrbitControls(camera, renderer.domElement);

        controls.enableDamping = true;
        controls.dampingFactor = 0.06;
        controls.enableZoom = true;
        controls.enablePan = true;

        // 6. Memuat model GLB lokal
        const loader = new GLTFLoader();

        loader.load(
            './assets/models/female_body_muscular_system_-_anatomy_study.glb',

            function (gltf) {
                const model = gltf.scene;

                scene.add(model);

                // Menghitung ukuran model secara otomatis
                const box = new THREE.Box3().setFromObject(model);
                const size = box.getSize(new THREE.Vector3());
                const center = box.getCenter(new THREE.Vector3());

                // Memusatkan model di titik tengah
                model.position.sub(center);

                const maxDimension = Math.max(
                    size.x,
                    size.y,
                    size.z
                );

                if (maxDimension > 0) {
                    const distance = maxDimension / (
                        2 * Math.tan(
                            THREE.MathUtils.degToRad(camera.fov / 2)
                        )
                    );

                    camera.position.set(
                        0,
                        0,
                        distance * 1.8
                    );

                    camera.near = Math.max(
                        distance / 1000,
                        0.001
                    );

                    camera.far = Math.max(
                        distance * 100,
                        100
                    );

                    camera.updateProjectionMatrix();

                    controls.target.set(0, 0, 0);
                    controls.minDistance = distance * 0.05;
                    controls.maxDistance = distance * 20;
                    controls.update();
                }

                status.style.display = 'none';

                console.log('Model berhasil dimuat:', model);
                console.log('Ukuran model:', size);
            },

            function (progress) {
                if (progress.total > 0) {
                    const percent = Math.round(
                        progress.loaded / progress.total * 100
                    );

                    status.textContent =
                        'Memuat model: ' + percent + '%';
                }
            },

            function (error) {
                console.error('Gagal memuat model:', error);

                status.textContent =
                    'Model gagal dimuat. Periksa lokasi file GLB ' +
                    'dan Console browser (F12).';
            }
        );

        // 7. Menyesuaikan ukuran saat jendela berubah
        function resizeViewer() {
            const width = viewer.clientWidth;
            const height = viewer.clientHeight;

            camera.aspect = width / height;
            camera.updateProjectionMatrix();

            renderer.setSize(width, height);
        }

        window.addEventListener('resize', resizeViewer);

        // 8. Menjalankan animasi
        function animate() {
            requestAnimationFrame(animate);

            controls.update();
            renderer.render(scene, camera);
        }

        animate();
    </script>

</body>
</html>
