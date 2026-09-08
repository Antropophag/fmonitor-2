# Assignment-order original setup — forked mysqli ownership gap

Date: `2026-09-05`

Status: **GATE 2 RESTART REQUIRED**.

The setup implementation reached the approved contention scenario. The test
forks after opening parent `$fixture` and `$admin` mysqli connections. The child
inherits those PHP objects and sockets; on child `exit()` their destructors send
close on the same server connection IDs. The parent then fails with `MySQL
server has gone away`, including on final cleanup.

This is independent of fixture locking behavior. Five leaked databases from the
failed cleanup were enumerated and removed only after matching exact bounded
`^t_aoou_[a-z]+_[0-9a-f]{12}$` ownership names. Production is preserved outside
the repository and removed from the worktree; task 2.2 is reopened.

The contention verifier must give the child a process image without inherited
mysqli sockets (bounded `proc_open`/exec worker), or explicitly rebuild every
parent connection after child exit while proving no shared socket destructor
can terminate live parent ownership. Fresh Gate 3 is required.
