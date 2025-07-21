<table>
    <thead>
        <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Created</th>
            <th>Role</th>
            <th>{{ config('settings.walletName') }}</th>            
        </tr>
    </thead>
    <tbody>
        @foreach ($users as $user)
        <tr>
            <td>{{ $user->name }}</td>
            <td>{{ $user->email }}</td>
            <td>{{ $user->phone }}</td>
            <td>{{ $user->created_at->diffForHumans() }}</td>
            <td>
                @foreach ($user->roles as $role)                
                {{ $role->name }}
                @endforeach
            </td>
            <td>
             {{ $user->balanceFloat }}
         </td>         
     </tr>
     @endforeach
 </tbody>
</table>