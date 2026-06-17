            </div>
@if ($standalone ?? request()->routeIs(\Pitbphp\Security\Support\SecurityRoutes::adminName('partials.*')))
        </main>
    </div>
</body>
</html>
@else
</div>
@endif
