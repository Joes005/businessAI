import React from 'react';
import { useNavigate } from 'react-router-dom';
import { Home } from 'lucide-react';
import EmptyState from '../components/ui/EmptyState';
import Button from '../components/ui/Button';

export default function NotFoundPage() {
  const navigate = useNavigate();

  return (
    <div style={{ padding: '60px 20px', display: 'flex', justifyContent: 'center' }}>
      <EmptyState
        title="404 - Page Not Found"
        description="The page or feature you are looking for does not exist or will be available in a future phase."
        action={
          <Button variant="primary" icon={Home} onClick={() => navigate('/')}>
            Back to Dashboard
          </Button>
        }
      />
    </div>
  );
}
