            </div> <!-- Close admin-content-container -->
        </main>
    </div> <!-- Close admin-layout -->

    <!-- Clock scripts -->
    <script>
        // Real-time server clock
        function updateClock() {
            const timeElem = document.getElementById('admin-time');
            if (timeElem) {
                const now = new Date();
                timeElem.innerText = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            }
        }
        setInterval(updateClock, 1000);
        updateClock();
    </script>
</body>
</html>
